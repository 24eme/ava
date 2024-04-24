#! /bin/bash

. bin/config.inc

mkdir $TMPDIR 2> /dev/null

if ! test "$ASSVAS_ETABLISSEMENT_FILE_URL"; then
    echo "La variable ASSVAS_ETABLISSEMENT_FILE_URL contenant l'url du fichier à importé n'est pas défini"
    exit;
fi

CSV_IMPORT="$TMPDIR"/import_etablissements_assvas.csv

CHECKSUM=$(md5sum $CSV_IMPORT)

mkdir -p $TMPDIR"/"$$
cd $TMPDIR"/"$$
wget -q $ASSVAS_ETABLISSEMENT_FILE_URL -O import.zip
unzip import.zip > /dev/null
mv import_openodg/*csv $CSV_IMPORT
cd - > /dev/null
rm -rf $TMPDIR"/"$$

if test "$CHECKSUM" = "$(md5sum $CSV_IMPORT)"; then
    exit;
fi

iconv -f iso-8859-15 -t utf-8 "$CSV_IMPORT" | tr "\n" "|" | sed -r 's/(\"[^\"]*)\|([^\"]*\")/\1\2/g' | tr "|" "\n" > "$CSV_IMPORT".utf8
php symfony import:etablissements-assvas $SYMFONYTASKOPTIONS "$CSV_IMPORT".utf8 --trace
