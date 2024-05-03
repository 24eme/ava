#!/bin/bash

ODG=ventoux

. bin/config.inc

DATA_DIR=$WORKINGDIR/import/igp/imports/$ODG
mkdir -p $DATA_DIR 2> /dev/null

if test "$1" = "--delete"; then

    echo -n "Delete database http://$COUCHHOST:$COUCHPORT/$COUCHBASE, type database name to confirm ($COUCHBASE) : "
    read databasename

    if test "$databasename" = "$COUCHBASE"; then
        curl -sX DELETE http://$COUCHHOST:$COUCHPORT/$COUCHBASE
        echo "Suppression de la base couchdb"
    fi
fi

echo "Création de la base couchdb"

curl -sX PUT http://$COUCHHOST:$COUCHPORT/$COUCHBASE

cd .. > /dev/null
make clean > /dev/null
make couchurl=http://$COUCHHOST:$COUCHPORT/$COUCHBASE > /dev/null
cd - > /dev/null

echo "Création des documents de configuration"

ls $WORKINGDIR/data/configuration/$ODG | while read jsonFile
do
    curl -s -X POST -d @data/configuration/$ODG/$jsonFile -H "content-type: application/json" http://$COUCHHOST:$COUCHPORT/$COUCHBASE
done

echo "Import des Opérateurs et Habilitations"

php symfony import:operateur-habilitation-ventoux $DATA_DIR/ventoux-operateurs-habilites.csv  --application="$ODG" --trace

echo "Import des opérateurs archivés"


echo "Import des chais"

ls $DATA_DIR/01_operateurs/fiches/*_identite.html | while read file; do NUM=$(echo -n $file | sed -r 's|.*/||' | sed 's/_identite.html//'); cat $file | tr "\n" " " | sed "s/<tr/\n<tr/g" | sed 's|</tr>|</tr>\n|' | grep "<tr" | sed 's|</td>|;|g' | sed 's|</th>|;|g' | sed 's/<[^>]*>//g' | sed -r 's/(^|;)[ \t]*/\1/g' | sed 's/&nbsp;/ /g' | grep -A 20 "Activité chai" | grep -Ev "^(Nouvelle adresse|Contrôle produit|Standard;Aléatoire)" | grep -Ev "^(Nom de site|Type|Adresse\*|CP\*):" | grep -v "^;$"  | grep -v "^;;$" | grep -v "^$" | sed -r 's/[ ]+/ /g' | sed 's/[ ]*;[ ]*/;/g' | grep -Ev "^Activité chai;" | grep -v ";Nouvelle adresse" | grep -vE "^;[0-9/]*;;;;;;;;" | sed -r "s|^|$NUM;|"; done > $DATA_DIR/01_operateurs/fiches_chais.csv

cat $DATA_DIR/07_chais/*.html | tr "\n" " " | sed "s/<tr/\n<tr/g" | sed 's|</tr>|</tr>\n|' | grep "<tr" | sed 's|</td>|;|g' | sed 's|</th>|;|g' | sed 's/<[^>]*>//g' | sed -r 's/(^|;)[ \t]*/\1/g' | sed 's/&nbsp;/ /g' | grep -Ev "^ ?;" | grep -Ev "^(Zone|ODG|Libelle|Raison Sociale|Nom)" | sed 's/^RaisonSociale/00RaisonSociale/' | sort | uniq > $DATA_DIR/chais.csv
php symfony import:chais $DATA_DIR/chais.csv $DATA_DIR/zones.csv --application="$ODG" --trace

echo "Import des responsables"

ls $DATA_DIR/01_operateurs/fiches/*_identite.html | while read file; do cat $file | grep "_tbResp" | grep "value" | sed 's/.*value="//' |  sed 's/".*//' | tr -d "\n"; echo $file | sed -r 's|.*/|;|' | sed 's/_identite.html//'; done | awk -F ";" '{ print ";" $1 ";;" sprintf("%06d", $2) ";;;;;;;;;;;;;;Responsable"  }' | grep -Ev "^;;;" > $DATA_DIR/membres_responsable.csv
php symfony import:interlocuteur-ia $DATA_DIR/membres_responsable.csv --nocreatesociete=1 --application="$ODG"

echo "Import des contacts"

echo -n > $DATA_DIR/contacts.csv
ls $DATA_DIR/01_operateurs/contacts/*.xlsx | while read file; do
    xlsx2csv -l '\r\n' -d ";" "$file" | tr -d "\n" | tr "\r" "\n" >> $DATA_DIR/contacts.csv;
done

cat $DATA_DIR/contacts.csv | awk -F ";" '{ if($1 == $7) { $7 = "" } if(($6 && $7) || $8) { print $6 ";" $7 ";" $8 ";" $1 ";;;;" $2 ";" $3 ";" $4 ";" $5 ";" $10 ";;" $11 ";" $12 ";;;" $9 }}' | sort | uniq > $DATA_DIR/contacts_formates.csv

php symfony import:interlocuteur-ia $DATA_DIR/contacts_formates.csv --nocreatesociete=1 --application="$ODG"

echo "Import DRev"

for annee in 2023 2022 2021 2020 2019 2018; do php symfony import:documents-douaniers "$annee" --dateimport="$annee-12-10" --application="$ODG"; done

echo -n > $DATA_DIR/drev.csv
ls $DATA_DIR/drev*.xlsx | sort -r | while read drev_file; do
    xlsx2csv -l '\r\n' -d ";" $drev_file | tr -d "\n" | tr "\r" "\n" >> $DATA_DIR/drev.csv
done;
echo -n > $DATA_DIR/vci.csv
ls $DATA_DIR/03_declarations/vci_* | while read vci_file; do
    MILLESIME=$(echo -n $vci_file | sed -r 's|^.*/vci_||' | sed 's/\.xlsx//')
    xlsx2csv -l '\r\n' -d ";" $vci_file | tr -d "\n" | tr "\r" "\n" | sed "s/^/$MILLESIME;/" >> $DATA_DIR/vci.csv
done;

bash bin/updateviews.sh

php symfony import:drev-ia $DATA_DIR/drev.csv $DATA_DIR/vci.csv --application="$ODG" --trace


echo "Contacts"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/contacts.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/contacts.csv
sed -i 's/Choisir Ville//' $DATA_DIR/contacts.csv
php symfony import:contact-ia $DATA_DIR/contacts.csv --application="$ODG" --trace

echo "Parcellaire"

php symfony parcellaire:update-aire --application="$ODG" --trace

curl -s http://$COUCHHOST:$COUCHPORT/$COUCHBASE/_design/etablissement/_view/all?reduce=false | cut -d '"' -f 4 | while read id; do php symfony import:parcellaire-douanier $id --application="$ODG" --noscrapping=1; done

echo "Import des declarations de pieds manquants"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/parcellaire_manquant_2023.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/parcellaire_manquant_2023.csv

php symfony import:parcellairemanquant-ventoux --application="$ODG" $DATA_DIR/parcellaire_manquant_2023.csv

echo "Mise a jour des relations en fonction des documents de production"

curl -s http://$COUCHHOST:$COUCHPORT/$COUCHBASE/_design/declaration/_view/tous\?reduce\=false | cut -d '"' -f 4 | grep 'DR-\|SV11-\|SV12-' | grep '\-2022' | while read id; do php symfony production:import-relation $id --application=centre; done

echo "Mise à jour des tags de compte"

bash bin/update_comptes_tags.sh
