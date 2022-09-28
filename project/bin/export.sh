#!/bin/bash

. bin/config.inc

ODG=$1

if test "$1"; then
    . bin/config_$ODG.inc
fi


mkdir $EXPORTDIR 2> /dev/null

APPLICATION=$(echo -n $SYMFONYTASKOPTIONS | sed -r 's/.*--application=([^ ]+).*/\1/')

php symfony export:etablissements-csv $SYMFONYTASKOPTIONS > $EXPORTDIR/etablissements.csv.part
cat $EXPORTDIR/etablissements.csv.part | sort | grep -E "^(Login)" > $EXPORTDIR/etablissements.csv.sorted.part
cat $EXPORTDIR/etablissements.csv.part | sort | grep -Ev "^(Login)" >> $EXPORTDIR/etablissements.csv.sorted.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/etablissements.csv.sorted.part > $EXPORTDIR/etablissements.en.csv
cat $EXPORTDIR/etablissements.en.csv | sed 's/;/ø/g' | awk -F ',' 'BEGIN { OFS=";" }{ $1=$1; print $0 }' | sed 's/ø/,/g' > $EXPORTDIR/etablissements.csv
rm $EXPORTDIR/etablissements.csv.part $EXPORTDIR/etablissements.csv.sorted.part
ln -s etablissements.en.csv $EXPORTDIR/etablissements.iso8859.csv 2> /dev/null # Pour l'AVPI en provence

sleep 60

php symfony export:chais-csv $SYMFONYTASKOPTIONS > $EXPORTDIR/chais.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/chais.csv.part > $EXPORTDIR/chais.en.csv
cat $EXPORTDIR/chais.en.csv | sed 's/;/ø/g' | awk -F ',' 'BEGIN { OFS=";" }{ $1=$1; print $0 }' | sed 's/ø/,/g' > $EXPORTDIR/chais.csv
rm $EXPORTDIR/chais.csv.part
ln -s chais.en.csv $EXPORTDIR/chais.iso8859.csv 2> /dev/null # Pour l'AVPI en provence

sleep 60

php symfony export:societe $SYMFONYTASKOPTIONS > $EXPORTDIR/societe.csv.part
head -n 1 $EXPORTDIR/societe.csv.part > $EXPORTDIR/societe.csv.part.head
tail -n +2 $EXPORTDIR/societe.csv.part | sort > $EXPORTDIR/societe.csv.part.body
cat  $EXPORTDIR/societe.csv.part.head $EXPORTDIR/societe.csv.part.body > $EXPORTDIR/societe.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/societe.csv.part > $EXPORTDIR/societe.iso.csv
rm $EXPORTDIR/societe.csv.part $EXPORTDIR/societe.csv.part.head $EXPORTDIR/societe.csv.part.body
mv -f $EXPORTDIR/societe.iso.csv $EXPORTDIR/societe.csv

sleep 60

bash bin/export_docs.sh DRev 30 > $EXPORTDIR/drev.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/drev.csv.part > $EXPORTDIR/drev.csv
rm $EXPORTDIR/drev.csv.part

bash bin/export_docs.sh Habilitation 30 > $EXPORTDIR/habilitation.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/habilitation.csv.part > $EXPORTDIR/habilitation.csv
rm $EXPORTDIR/habilitation.csv.part

php symfony export:habilitation-demandes $SYMFONYTASKOPTIONS > $EXPORTDIR/habilitation_demandes.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/habilitation_demandes.csv.part > $EXPORTDIR/habilitation_demandes.csv
php bin/export/export_liste_inao.php $EXPORTDIR/habilitation_demandes.csv.part | grep -E "^(Côtes du Rhône|Libelle Appellation)" > $EXPORTDIR/habilitation_demandes_inao.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/habilitation_demandes_inao.csv.part > $EXPORTDIR/habilitation_demandes_inao.csv
rm $EXPORTDIR/habilitation_demandes.csv.part $EXPORTDIR/habilitation_demandes_inao.csv.part

echo $EXPORT_SUB_HABILITATION | tr '|' '\n' | grep '[A-Z]' | while read subhab; do
    eval 'SUBDIR=$EXPORT_SUB_HABILITATION_'$subhab'_DIR'
    eval 'SUBFILTRE=$EXPORT_SUB_HABILITATION_'$subhab'_FILTRE'
    mkdir -p $SUBDIR
    head -n 1 $EXPORTDIR/habilitation.csv > $SUBDIR/habilitation.csv
    cat $EXPORTDIR/habilitation.csv | iconv -f ISO88591 -t UTF8 | grep "$SUBFILTRE" | iconv -f UTF8 -t ISO88591 >> $SUBDIR/habilitation.csv
    head -n 1 $EXPORTDIR/drev.csv > $SUBDIR/drev.csv
    cat $EXPORTDIR/drev.csv | iconv -f ISO88591 -t UTF8 | grep "$SUBFILTRE" | iconv -f UTF8 -t ISO88591  >> $SUBDIR/drev.csv
done

sleep 60

bash bin/export_docs.sh DR 30 > $EXPORTDIR/dr.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/dr.csv.part > $EXPORTDIR/dr.csv
rm $EXPORTDIR/dr.csv.part

bash bin/export_docs.sh SV12 30 > $EXPORTDIR/sv12.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/sv12.csv.part > $EXPORTDIR/sv12.csv
rm $EXPORTDIR/sv12.csv.part

bash bin/export_docs.sh SV11 30 > $EXPORTDIR/sv11.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/sv11.csv.part > $EXPORTDIR/sv11.csv
rm $EXPORTDIR/sv11.csv.part

bash bin/export_docs.sh ParcellaireIrrigable > $EXPORTDIR/parcellaireirrigable.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/parcellaireirrigable.csv.part > $EXPORTDIR/parcellaireirrigable.csv
rm $EXPORTDIR/parcellaireirrigable.csv.part

bash bin/export_docs.sh ParcellaireIrrigue > $EXPORTDIR/parcellaireirrigue.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/parcellaireirrigue.csv.part > $EXPORTDIR/parcellaireirrigue.csv
rm $EXPORTDIR/parcellaireirrigue.csv.part

bash bin/export_docs.sh ParcellaireIntentionAffectation > $EXPORTDIR/parcellaireintentionaffectation.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/parcellaireintentionaffectation.csv.part > $EXPORTDIR/parcellaireintentionaffectation.csv
rm $EXPORTDIR/parcellaireintentionaffectation.csv.part

bash bin/export_docs.sh ParcellaireAffectation > $EXPORTDIR/parcellaireaffectation.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/parcellaireaffectation.csv.part > $EXPORTDIR/parcellaireaffectation.csv
rm $EXPORTDIR/parcellaireaffectation.csv.part

#sleep 60

php symfony pieces:export-csv $SYMFONYTASKOPTIONS >  $EXPORTDIR/pieces.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/pieces.csv.part > $EXPORTDIR/pieces.csv
rm $EXPORTDIR/pieces.csv.part

#sleep 60

php symfony liaisons:export-csv $SYMFONYTASKOPTIONS >  $EXPORTDIR/liaisons.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/liaisons.csv.part > $EXPORTDIR/liaisons.csv
rm $EXPORTDIR/liaisons.csv.part

#sleep 60

php symfony compte:export-all-csv $SYMFONYTASKOPTIONS >  $EXPORTDIR/comptes.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/comptes.csv.part > $EXPORTDIR/comptes.csv
rm $EXPORTDIR/comptes.csv.part

echo "id;identifiant;campagne;statut;region;date;origine_document_id;produit_hash;produit_libelle;produit_couleur;volume;date lot;elevage;millesime;region;numero_dossier;numero_archive;numero_cuve;version;origine_hash;origine_type;origine_document_id lot;origine_mouvement;declarant_identifiant;declarant_nom;destination_type;destination_date;details;campagne lot;id_document;statut lot;specificite;centilisation;conformite;motif" > $EXPORTDIR/lots.csv.part
curl -s "http://$COUCHHOST:$COUCHDBPORT/$COUCHBASE/_design/mouvement/_view/lot" | grep id | grep -v '"key":\[null' | sed 's/{"id"://' | sed 's/,"key":\[/,/' | sed 's/null//g' | sed 's/"value":{//' | sed -r 's/,"[a-z_]+":/,/g' | sed 's/}},//' | sed 's/\],/,/' | sed 's/","/";"/g' | sed 's/,,,/;;;/g' | sed 's/,,/;;/g' | sed -r 's/",/";/g'| sed 's/,"/;"/g' | grep --color "," | sed -r 's/(false|true),/\1;/g' | sed -r 's/,(false|true)/;\1/g' >> $EXPORTDIR/lots.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/lots.csv.part > $EXPORTDIR/lots.csv
rm $EXPORTDIR/lots.csv.part

php symfony export:csv-configuration $SYMFONYTASKOPTIONS > $EXPORTDIR/produits.csv.part
iconv -f UTF8 -t ISO88591//TRANSLIT $EXPORTDIR/produits.csv.part > $EXPORTDIR/produits.csv
rm $EXPORTDIR/produits.csv.part

mkdir -p $EXPORTDIR/stats

cd bin/notebook/
ls "$APPLICATION"_*.py | while read script; do python3 $script;done
cd -

find $EXPORTDIR -type f -empty -delete

if test "$METABASE_SQLITE"; then
    python3 bin/csv2sql.py $METABASE_SQLITE".tmp" $EXPORTDIR
    mv $METABASE_SQLITE".tmp" $METABASE_SQLITE
fi
