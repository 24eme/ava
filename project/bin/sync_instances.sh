#!/bin/bash

# Mode multi app
if ! test -f $(echo $0 | sed 's/[^\/]*$//')config.inc && ! test $1 ; then
    ls . $(echo $0 | sed 's/[^\/]*$//') | grep "config_" | grep ".inc$" | sed 's/config_//' | sed 's/\.inc//' | while read app; do
        bash $(echo $0 | sed 's/[^\/]*$//')sync_instances $app;
    done
    exit 0
fi

if ! test $1 ; then
    . $(echo $0 | sed 's/[^\/]*$//')config.inc
else
    . $(echo $0 | sed 's/[^\/]*$//')config_"$1".inc
fi

rsync -aO $WORKINGDIR"/web/generation/" $COUCHDISTANTHOST":"$WORKINGDIR"/web/generation"
rsync -aO $WORKINGDIR"/"$EXPORTDIR"/" $COUCHDISTANTHOST":"$WORKINGDIR"/"$EXPORTDIR
if test "$EXTRA_SYNC" then
    rsync -aO $EXTRA_SYNC"/" $COUCHDISTANTHOST":"$EXTRA_SYNC"
fi