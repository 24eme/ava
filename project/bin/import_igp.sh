#!/bin/bash

if ! test "$1"; then
    echo "Nom du dossier/de l'ODG";
    exit 1;
fi

ODG=$1

. bin/config_$ODG.inc

EXPORT=$2

if test "$EXPORT"; then
  if test "$EXPORT" = "-exp"; then
    echo "Export données";
    cd $WORKINGDIR/import/igp/;
    bash scrapping.sh configs/config.$ODG.json;
    cd $WORKINGDIR;
  fi
fi

DATA_DIR=$WORKINGDIR/import/igp/imports/$ODG
mkdir -p $DATA_DIR 2> /dev/null

if test "$2" = "--delete"; then

    echo -n "Delete database http://$COUCHHOST:$COUCHPORT/$COUCHBASE, type database name to confirm ($COUCHBASE) : "
    read databasename

    if test "$databasename" = "$COUCHBASE"; then
        curl -sX DELETE http://$COUCHHOST:$COUCHPORT/$COUCHBASE
        echo "Suppression de la base couchdb"
    fi

    if test "$3"; then
      if test "$3" = "-exp"; then
        echo "Export données"
        cd $WORKINGDIR/import/igp/
        bash scrapping.sh config_$ODG.json
        cd $WORKINGDIR
      fi
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

echo "Import des Opérateurs"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/operateurs.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/operateurs.csv
php symfony import:operateur-ia $DATA_DIR/operateurs.csv --application="$ODG" --trace

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/apporteurs_de_raisins.xlsx | tr -d "\n" | tr "\r" "\n" | awk -F ";" 'BEGIN { OFS=";"} { $4=""; $3=";Producteur de raisin"; print $0 }' | sort | uniq > $DATA_DIR/apporteurs_de_raisins.csv
php symfony import:operateur-ia $DATA_DIR/apporteurs_de_raisins.csv --application="$ODG" --trace

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/operateurs_inactifs.xlsx | tr -d "\n" | tr "\r" "\n" | awk -F ";" 'BEGIN { OFS=";"} { $3=$3 ";;"; $21="SUSPENDU"; print $0 }' > $DATA_DIR/operateurs_inactifs.csv
php symfony import:operateur-ia $DATA_DIR/operateurs_inactifs.csv --application="$ODG" --trace

echo "Contacts"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/contacts.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/contacts.csv
php symfony import:contact-ia $DATA_DIR/contacts.csv --application="$ODG" --trace

echo "Import des interlocuteurs"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/membres.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/membres.csv
php symfony import:interlocuteur-ia $DATA_DIR/membres.csv --application="$ODG" --trace

echo "Habilitations"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/habilitations.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/habilitations.csv
php symfony import:habilitation-ia $DATA_DIR/habilitations.csv --application="$ODG" --trace

echo "Lots"

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/lots.xlsx | tr -d "\n" | tr "\r" "\n" | sort -t ";" -k 3,4 -k 24 > $DATA_DIR/lots.csv
sed -i 's/;"200;1+CF80;1";/;"200 1+CF80 1";/' $DATA_DIR/lots.csv
sed -i 's/;"4+CF100;3";/;"4+CF100 3";/' $DATA_DIR/lots.csv
php symfony import:lots-ia $DATA_DIR/lots.csv --application="$ODG" --trace

echo "Changement de denomination"

xls2ods $DATA_DIR/changement_denom.xls
ods2tsv $DATA_DIR/changement_denom.ods | sed 's/;/ /g' | sed 's/\t/;/g' > $DATA_DIR/changement_denom.csv
php symfony import:chgt-denom-ia $DATA_DIR/changement_denom.csv --application="$ODG" --trace

echo "Degustations"
# trie des lots par date de commission pour dire qu'une date correspond à une degustation.
sort -t";" -k32.7,32.10 -k32.4,32.5 -k32.1,32.2 $DATA_DIR/lots.csv  > $DATA_DIR/lots_sort_by_date.csv
# il y a également un problème avec le nom de lieu j'ai mis SYNDICAT-VIGNERONS-ARLES par défaut mais il faudra le changer en fonction de ce qu'il y a en base pour chaque lot ce champs n'est pas dans le csv exporté.
php symfony import:degustations-ia $DATA_DIR/lots_sort_by_date.csv --application="$ODG" --trace

xlsx2csv -l '\r\n' -d ";" $DATA_DIR/gestion_nc.xlsx | tr -d "\n" | tr "\r" "\n" > $DATA_DIR/gestion_nc.csv
php symfony import:degustations-non-conformite-ia $DATA_DIR/gestion_nc.csv --application="$ODG" --trace
