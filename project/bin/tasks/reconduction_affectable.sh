#! /bin/bash
#
# Title: Reconduction des affectations
# Description: Reconduit les affectations parcellaires depuis la déclaration de l'année précédente

source "$(dirname $0)/../config.inc"

CAMPAGNE="$(date +%Y -d'1 year ago')"
cd $WORKINGDIR
bash bin/copy_declarations_to_next_campagne.sh PARCELLAIREAFFECTATION "$CAMPAGNE"
