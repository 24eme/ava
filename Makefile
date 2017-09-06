all: .views/etablissements.json .views/societe.json .views/compte.json .views/declaration.json .views/piece.json

.views/societe.json: project/config/databases.yml project/plugins/acVinSocietePlugin/lib/model/views/societe.all.reduce.view.js project/plugins/acVinSocietePlugin/lib/model/views/societe.all.map.view.js project/plugins/acVinSocietePlugin/lib/model/views/societe.export.map.view.js .views
	perl bin/generate_views.pl project/config/databases.yml project/plugins/acVinSocietePlugin/lib/model/views/societe.all.reduce.view.js project/plugins/acVinSocietePlugin/lib/model/views/societe.all.map.view.js project/plugins/acVinSocietePlugin/lib/model/views/societe.export.map.view.js > $@ || rm >@

.views/etablissements.json: project/config/databases.yml  project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.findByCvi.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.region.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.all.map.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.findByCvi.map.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.all.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.douane.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.region.map.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.douane.map.view.js .views
		perl bin/generate_views.pl project/config/databases.yml  project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.findByCvi.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.region.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.all.map.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.findByCvi.map.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.all.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.douane.reduce.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.region.map.view.js project/plugins/acVinEtablissementPlugin/lib/model/views/etablissement.douane.map.view.js  > $@ || rm >@

.views/compte.json: project/config/databases.yml project/plugins/acVinComptePlugin/lib/model/views/compte.all.reduce.view.js project/plugins/acVinComptePlugin/lib/model/views/compte.all.map.view.js  project/plugins/acVinComptePlugin/lib/model/views/compte.tags.reduce.view.js project/plugins/acVinComptePlugin/lib/model/views/compte.tags.map.view.js .views
	perl bin/generate_views.pl project/config/databases.yml project/plugins/acVinComptePlugin/lib/model/views/compte.all.reduce.view.js project/plugins/acVinComptePlugin/lib/model/views/compte.all.map.view.js project/plugins/acVinComptePlugin/lib/model/views/compte.tags.reduce.view.js project/plugins/acVinComptePlugin/lib/model/views/compte.tags.map.view.js > $@ || rm >@

.views/declaration.json: project/config/databases.yml project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.tous.map.view.js project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.tous.reduce.view.js project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.identifiant.map.view.js project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.identifiant.reduce.view.js .views
	perl bin/generate_views.pl project/config/databases.yml project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.tous.reduce.view.js project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.tous.map.view.js	project/plugins/DeclarationPlugin/lib/Declaration/view/declaration.identifiant.map.view.js > $@ || rm >@

.views/piece.json: project/config/databases.yml project/plugins/acVinDocumentPlugin/lib/Piece/views/piece.all.map.view.js project/plugins/acVinDocumentPlugin/lib/Piece/views/piece.all.reduce.view.js .views
		perl bin/generate_views.pl project/config/databases.yml project/plugins/acVinDocumentPlugin/lib/Piece/views/piece.all.reduce.view.js  project/plugins/acVinDocumentPlugin/lib/Piece/views/piece.all.map.view.js > $@ || rm >@

.views:
	mkdir .views

clean:
	rm -f .views/*
