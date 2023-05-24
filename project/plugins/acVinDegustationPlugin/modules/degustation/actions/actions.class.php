<?php

class degustationActions extends sfActions {

    public function executeIndex(sfWebRequest $request) {
        $this->form = new DegustationCreationForm();

        $this->lotsPrelevables = DegustationClient::getInstance()->getLotsPrelevables();
        $this->lotsElevages = MouvementLotView::getInstance()->getByStatut(Lot::STATUT_ELEVAGE_EN_ATTENTE)->rows;
        $this->lotsManquements = MouvementLotView::getInstance()->getByStatut(Lot::STATUT_MANQUEMENT_EN_ATTENTE)->rows;

        $this->campagne = ConfigurationClient::getInstance()->getCampagneManager()->getCurrent();

        $this->degustations = DegustationClient::getInstance()->getHistory(10, "", acCouchdbClient::HYDRATE_JSON);

        if(class_exists("EtablissementChoiceForm")) {
            $this->formEtablissement = new EtablissementChoiceForm('INTERPRO-declaration', array(), true);
        }

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $degustation = $this->form->save();

        return $this->redirect('degustation_selection_lots', $degustation);
    }

    public function executeEtablissementSelection(sfWebRequest $request) {
        $form = new EtablissementChoiceForm('INTERPRO-declaration', array(), true);
        $form->bind($request->getParameter($form->getName()));
        if (!$form->isValid()) {
            $this->redirect('degustation');
        }

        return $this->redirect('degustation_declarant_lots_liste', $form->getEtablissement());
    }

    public function executeListe(sfWebRequest $request)
    {
        $this->annee = $request->getParameter('campagne');
        $this->degustations = DegustationClient::getInstance()->getHistory(9999, $this->annee, acCouchdbClient::HYDRATE_JSON);
    }

    public function executeListeDeclarant(sfWebRequest $request)
    {
        $this->campagne = $request->getParameter('campagne', ConfigurationClient::getInstance()->getCampagneVinicole()->getCurrent());
        $this->etablissement = $request->getParameter('identifiant');
        $this->degustations = [];

        $mouvements = MouvementLotHistoryView::getInstance()->getMouvementsByDeclarant($this->etablissement, $this->campagne)->rows;

        foreach ($mouvements as $lot) {
            if (in_array($lot->value->document_id, $this->degustations)) {
                continue;
            }

            $this->degustations[$lot->value->document_id] = DegustationClient::getInstance()->find($lot->value->document_id, acCouchdbClient::HYDRATE_JSON);
        }
    }

    public function executePrelevables(sfWebRequest $request)
    {
        $this->lotsPrelevables = DegustationClient::getInstance()->getLotsPrelevables();
    }

    public function executeSelectionLots(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        $this->redirectIfIsAnonymized();

        if ($this->degustation->getNbLotsPreleves()) {
            return sfView::ALERT;
        }

        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_LOTS))) {
            $this->degustation->save(false);
        }

        $this->form = new DegustationSelectionLotsForm($this->degustation);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->save();

        return ($next = $this->getRouteNextEtape(DegustationEtapes::ETAPE_LOTS))? $this->redirect($next, $this->degustation) : $this->redirect('degustation');
    }

    public function executePreleve(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();

        $this->form = new DegustationPreleveLotsForm($this->degustation);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->save();

        if ($request->isXmlHttpRequest()) {
            return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->degustation->_id, "revision" => $this->degustation->_rev))));
        }

        return $this->redirect('degustation_prelevements_etape', $this->degustation);
    }

    public function executeSupprimerLotNonPreleve(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $lot = $this->degustation->lots->get($request->getParameter('lot'));
        $this->degustation->removeLot($lot);
        $this->degustation->save();
        return $this->redirect('degustation_preleve', $this->degustation);

    }

    public function executeUpdateLotLogement(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->lot = $request->getParameter('lot');

        $this->form = new DegustationPreleveUpdateLogementForm($this->degustation, $this->lot);

        if ($request->isMethod(sfWebRequest::POST)) {
            $this->form->bind($request->getParameter($this->form->getName()));

            if ($this->form->isValid()) {
                $this->form->save();
                return $this->redirect('degustation_preleve', $this->degustation);
            }
        }
    }

    public function executeAjoutDegustateurPresence(sfWebRequest $request){
        $this->degustation = $this->getRoute()->getDegustation();
        $this->table = $request->getParameter('table', null);

        $this->form = new DegustationAjoutDegustateurForm($this->degustation, array(), array('table' => $this->table));

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->form->save();

        if (!$this->table) {
          $this->redirect('degustation_degustateurs_confirmation', array('id' =>$this->degustation->_id));
        }

        return $this->redirect('degustation_presences', array('id' =>$this->degustation->_id, 'numero_table' => $this->table));
    }

    public function executeSelectionDegustateurs(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        $this->colleges = DegustationConfiguration::getInstance()->getColleges();
        $first_college = array_key_first($this->colleges);

        $this->previous_college = null;
        if(!$this->college = $request->getParameter('college')) {

            return $this->redirect('degustation_selection_degustateurs', array('id' => $this->degustation->_id, 'college' => $first_college));
        }

        $colleges_keys = array_keys($this->colleges);
        $currentCollegeKey = array_search($this->college, $colleges_keys);
        $next_college = ($currentCollegeKey+1 >= count($colleges_keys))? null : $colleges_keys[$currentCollegeKey+1];
        $this->previous_college = ($currentCollegeKey-1 < 0 )? null : $colleges_keys[$currentCollegeKey-1];

        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_DEGUSTATEURS))) {
            $this->degustation->save(false);
        }

        $this->form = new DegustationSelectionDegustateursForm($this->degustation,array(),array('college' => $this->college));

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));
        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->form->save();

        if ($request->isXmlHttpRequest()) {
          return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->degustation->_id, "revision" => $this->degustation->_rev))));
        }

        if(!$next_college){
          return $this->redirect('degustation_convocations', $this->degustation);
        }

        return $this->redirect('degustation_selection_degustateurs', array('id' => $this->degustation->_id ,'college' => $next_college));
    }


    public function executeConvocations(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_CONVOCATIONS))) {
            $this->degustation->save(false);
          }
    }

    public function executeConvocationsMails(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        Email::getInstance()->sendConfirmationDegustateursMails($this->degustation);
        $this->getUser()->setFlash("notice", "Les mails de convocations ont été envoyés aux dégustateurs.");
        return $this->redirect('degustation_convocations', $this->degustation);
    }

    public function executeConvocationDegustateurMail(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $id_compte = $request->getParameter('id_compte');
        $college_key = $request->getParameter('college_key');
        Email::getInstance()->sendConfirmationDegustateurMail($this->degustation, $id_compte, $college_key);
        $this->getUser()->setFlash("notice", "Le mail de convocation a été envoyé au dégustateur.");
        return $this->redirect('degustation_degustateurs_confirmation', $this->degustation);
    }


    public function executePrelevementsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_PRELEVEMENTS))) {
            $this->degustation->save(false);
        }
    }


    /**
     * Les tournées par opérateur
     * @param sfWebRequest $request
     * @return void
     */
    public function executeTourneesEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        $this->secteurs = [];
        for ($i = 0; $i < 4; $i++) {
            $sect_name = 'Secteur ' . $i;
            $sect_key = urlencode(str_replace(' ', '-', $sect_name));
            $this->secteurs[$sect_key] = $sect_name;
        }
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_TOURNEES))) {
            $this->degustation->save(false);
        }
    }

    public function executeTablesEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        if (count($this->degustation->getLotsDegustables()) < 1) {
            return $this->redirect($this->getRouteEtape(DegustationEtapes::ETAPE_PRELEVEMENTS), $this->degustation);
        }
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_TABLES))) {
            $this->degustation->save(false);
        }
    }

    public function executeAnonymatsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        if ($this->degustation->getNbLotsRestantAPreleve() > 0) {
            $this->getUser()->setFlash('error', 'Il reste des lots à prélever');
            return $this->redirect($this->getRouteEtape(DegustationEtapes::ETAPE_PRELEVEMENTS), $this->degustation);
        }
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_ANONYMATS))) {
            $this->degustation->save(false);
          }
    }

    public function executeCommissionEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsNotAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_COMMISSION))) {
            $this->degustation->save(false);
          }
    }

    public function executeResultatsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsNotAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_RESULTATS))) {
            $this->degustation->save();
          }
    }

    public function executeNotificationsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsNotAnonymized();

        $this->mail_to_identifiant = $request->getParameter('mail_to_identifiant');

        if (!$this->degustation->areAllLotsSaisis()) {
            $this->getUser()->setFlash('error', "Il reste des lots sans résultats (conformes/non-conformes). Merci de les saisir !");
            return $this->redirect($this->getRouteEtape(DegustationEtapes::ETAPE_RESULTATS), $this->degustation);
        }

        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_NOTIFICATIONS))) {
            $this->degustation->save();
        }
    }

    public function executeExportCsv(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();

        $export = new ExportDegustationCSV($this->degustation);

        $this->response->setContent(utf8_decode($export->export()));
        $this->response->setContentType('text/csv');
        $this->response->setHttpHeader('Content-Disposition', "attachment; filename=".$export->getFileName());

        return sfView::NONE;

        $attachement = "attachment; filename=".$export->getFileName();
        $this->response->setContentType('text/csv');
        $this->response->setHttpHeader('Content-Disposition',$attachement);

        return $this->renderText($export->export());
    }

    public function executeDegustateursConfirmation(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
      $this->form = new DegustationDegustateursConfirmationForm($this->degustation);

      if (!$request->isMethod(sfWebRequest::POST)) {

          return sfView::SUCCESS;
      }

      $this->form->bind($request->getParameter($this->form->getName()));

      if (!$this->form->isValid()) {
          return sfView::SUCCESS;
      }
      $this->form->save();

      if($this->degustation->isAnonymized()) {

        return $this->redirect('degustation_commission_etape', $this->degustation);
      }

      return $this->redirect('degustation_prelevements_etape', $this->degustation);
    }

    public function executeDegustateurAbsence(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();

      $college = $request->getParameter('college',null);
      $degustateurId = $request->getParameter('degustateurId',null);
      if(!$college || !$degustateurId){
        return $this->redirect('degustation_degustateurs_confirmation', $this->degustation);
      }

      $this->degustation->degustateurs->getOrAdd($college)->getOrAdd($degustateurId)->add('confirmation',false);

      if($this->degustation->degustateurs->get($college)->get($degustateurId)->exist('numero_table') && $this->degustation->degustateurs->get($college)->get($degustateurId)->numero_table != null) {
         throw new sfError403Exception("Vous n'êtes pas autorisé à marquer l'absence de ce dégustateur car il est déjà affecté à une table");
      }

      $this->degustation->save(false);

      return $this->redirect('degustation_degustateurs_confirmation', $this->degustation);

    }

    public function executeOrganisationTable(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();

        if (DegustationConfiguration::getInstance()->isAnonymisationManuelle()) {
            $this->forward('degustation', 'organisationTableManuelle');
        }

        $this->redirectIfIsAnonymized();

        if(!$request->getParameter('numero_table')) {
            return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => 1));
        }

        $this->numero_table = $request->getParameter('numero_table');

        $this->tri = $this->degustation->tri;
        $this->tri_array = explode('|', strtolower($this->tri));

        $this->syntheseLots = $this->degustation->getSyntheseLotsTableCustomTri($this->numero_table);
        $this->form = new DegustationOrganisationTableForm($this->degustation, $this->numero_table);
        $this->ajoutLeurreForm = new DegustationAjoutLeurreForm($this->degustation, array('table' => $this->numero_table));
        $this->triTableForm = new DegustationTriTableForm($this->degustation->getTriArray(), false);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }
        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }
        $this->form->save();

        if ($request->isXmlHttpRequest()) {

          return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->degustation->_id, "revision" => $this->degustation->_rev))));
        }

        if(!count($this->degustation->getLotsTableOrFreeLots($this->numero_table, false)) && $this->degustation->hasFreeLots()) {

            return $this->redirect('degustation_organisation_table_recap', array('id' => $this->degustation->_id, 'tri' => $this->tri));
        }

        if($this->degustation->hasFreeLots()) {

            return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table + 1, 'tri' => $this->tri));
        }

        return $this->redirect('degustation_organisation_table_recap', array('id' => $this->degustation->_id, 'tri' => $this->tri));
    }

    public function executeChangePositionLot(sfWebRequest $request) {
        $degustation = $this->getRoute()->getDegustation();
        $index = $request->getParameter('index');
        $tri = $degustation->tri;
        $sens = $request->getParameter('sens');
        $numero_table = $request->getParameter('numero_table');

        $this->forward404Unless($degustation->lots->exist($index));
        $lot = $degustation->lots->get($index);
        $lot->changePosition($sens);

        $degustation->save(false);
        return $this->redirect($this->generateUrl('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table, 'tri' => $degustation->tri))."#form-organisation-table");
    }

    public function executeOrganisationTableRecap(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->tri = $this->degustation->tri;
        $this->triTableForm = new DegustationTriTableForm($this->degustation->getTriArray(), true);

        $this->syntheseLots = $this->degustation->getSyntheseLotsTable(null);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        return $this->redirect('degustation_tables_etape', $this->degustation);
    }

    public function executeOrganisationTableManuelle(sfWebRequest $request)
    {
        $this->degustation = $this->getRoute()->getDegustation();

        if(!$request->getParameter('numero_table')) {
            return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => 1));
        }

        $this->numero_table = $request->getParameter('numero_table');
        $this->form = new DegustationOrganisationManuelleForm($this->degustation, $this->numero_table);
        $this->ajoutLeurreForm = new DegustationAjoutLeurreForm($this->degustation, ['table' => $this->numero_table]);

        if (! $request->isMethod(sfWebRequest::POST)) {
            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (! $this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->form->save();

        if ($this->degustation->hasFreeLots()) {
            return $this->redirect('degustation_organisation_table', ['id' => $this->degustation->_id, 'numero_table' => $this->numero_table + 1]);
        }

        return $this->redirect('degustation_organisation_table_recap', ['id' => $this->degustation->_id]);
    }

    public function executeAjoutLeurre(sfWebRequest $request){
        $this->degustation = $this->getRoute()->getDegustation();
        $this->ajoutLeurreForm = new DegustationAjoutLeurreForm($this->degustation);
        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }
        $this->ajoutLeurreForm->bind($request->getParameter($this->ajoutLeurreForm->getName()));

        if (!$this->ajoutLeurreForm->isValid()) {

            $this->getUser()->setFlash('error', 'Formulaire d\'ajout de leurre invalide');
            return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => 0));
        }
        $this->ajoutLeurreForm->save();

        $table = $this->ajoutLeurreForm->getValue('table');
        if ($table == null) {
            $table = 0;
        }

        return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => $table));
    }

      public function executeResultats(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsNotAnonymized();
        $this->numero_table = $request->getParameter('numero_table',0);
        $this->popup_validation = $request->getParameter('popup',0);

        if(!$this->numero_table && $this->degustation->getFirstNumeroTable()){
          return $this->redirect('degustation_resultats', array('id' => $this->degustation->_id, 'numero_table' => $this->degustation->getFirstNumeroTable()));
        }

        $this->tableLots = $this->degustation->getLotsByTable($this->numero_table);
        $this->nb_tables = count($this->degustation->getTables());
        $options = array('numero_table' => $this->numero_table);
        $this->form = new DegustationResultatsForm($this->degustation, $options);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));
        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->form->save();

        if($this->popup_validation){
          return $this->redirect('degustation_resultats', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table));
        }

        if($this->numero_table != $this->nb_tables){
          return $this->redirect('degustation_resultats', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table+1));
        }

        return $this->redirect('degustation_resultats_etape', $this->degustation);
    }


    public function executePresences(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsNotAnonymized();
        $this->numero_table = $request->getParameter('numero_table',0);

        if(!$this->numero_table && $this->degustation->getFirstNumeroTable()){
          return $this->redirect('degustation_presences', array('id' => $this->degustation->_id, 'numero_table' => $this->degustation->getFirstNumeroTable()));
        }

        $this->nb_tables = count($this->degustation->getTables());
        $options = array('numero_table' => $this->numero_table);
        $this->form = new DegustationDegustateursTableForm($this->degustation, $options);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->form->save();

        if($this->numero_table && ($this->numero_table < $this->degustation->getLastNumeroTable())){
          return $this->redirect('degustation_presences', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table+1));
        }

        return $this->redirect('degustation_resultats_etape', $this->degustation);
    }

    public function executeVisualisation(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $etape = $this->getRouteEtape($this->degustation->etape);
        if(!$etape){

            return $this->redirect('degustation_selection_lots', $this->degustation);
        }

        return $this->redirect($etape, $this->degustation);
    }


    protected function getEtape($doc, $etape, $class = "DegustationEtapes") {
        $etapes = $class::getInstance();
        if (!$doc->exist('etape')) {
            return $etape;
        }
        return ($etapes->isLt($doc->etape, $etape)) ? $etape : $doc->etape;
    }

    protected function getRouteEtape($etape = null, $class = "DegustationEtapes") {
        $etapes = $class::getInstance();
        $routes = $etapes->getRouteLinksHash();

        return (isset($routes[$etape]))? $routes[$etape] : null;
    }

    protected function getRouteNextEtape($etape = null, $class = "DegustationEtapes") {
        $etapes = $class::getInstance();
        $routes = $etapes->getRouteLinksHash();
        if (!$etape) {
            $etape = $etapes->getFirst();
        } else {
            $etape = $etapes->getNext($etape);
        }
        return (isset($routes[$etape]))? $routes[$etape] : null;
    }

    public function executeLotHistorique(sfWebRequest $request){
        $identifiant = $request->getParameter('identifiant');
        $uniqueId = $request->getParameter('unique_id');

        $this->lot = LotsClient::getInstance()->findByUniqueId($identifiant, $uniqueId);

        if(!$this->lot) {

            throw new sfError404Exception("Lot non trouvé");
        }

        $this->etablissement = EtablissementClient::getInstance()->findByIdentifiant($identifiant);
        $this->mouvements = LotsClient::getInstance()->getHistory($identifiant, $uniqueId);
    }

    public function executeLotModification(sfWebRequest $request){
        $identifiant = $request->getParameter('identifiant');
        $uniqueId = $request->getParameter('unique_id');

        $this->etablissement = EtablissementClient::getInstance()->findByIdentifiant($identifiant);
        $this->lot = LotsClient::getInstance()->findByUniqueId($identifiant, $uniqueId);

        if(!$this->lot) {

            throw new sfError404Exception("Lot non trouvé");
        }

        if (!$this->lot->getDocument()->getMaster()->verifyGenerateModificative()) {
            $this->getUser()->setFlash('error', "Le lot n'est pas modifiable : un document est sans doute en cours d'édition");
            return $this->redirect('degustation_lot_historique', array('identifiant' => $this->etablissement->identifiant, 'unique_id' => $this->lot->unique_id));
        }

        $this->form = new LotModificationForm($this->lot);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->form->save();

        return $this->redirect('degustation_lot_historique', array('identifiant' => $this->etablissement->identifiant, 'unique_id' => $this->lot->unique_id));
    }

    public function executeLotDelete(sfWebRequest $request) {
        $lot = LotsClient::getInstance()->findByUniqueId($request->getParameter('identifiant'), $request->getParameter('unique_id'));

        if(!$lot) {

            throw new sfError404Exception("Lot non trouvé");
        }

        LotsClient::getInstance()->deleteAndSave($lot->declarant_identifiant, $lot->unique_id);

        return $this->redirect('degustation_declarant_lots_liste',array('identifiant' => $lot->declarant_identifiant, 'campagne' => $lot->campagne));
    }

    public function executeLotsListe(sfWebRequest $request) {
        $identifiant = $request->getParameter('identifiant');
        $this->etablissement = EtablissementClient::getInstance()->find($identifiant);
        $this->forward404Unless($this->etablissement);

        if(class_exists("EtablissementChoiceForm")) {
            $this->formEtablissement = new EtablissementChoiceForm(sfConfig::get('app_interpro', 'INTERPRO-declaration'), array('identifiant' => $this->etablissement->identifiant), true);
        } elseif(class_exists("LoginForm")) {
            $this->formEtablissement = new LoginForm();
        }

        $this->campagnes = MouvementLotHistoryView::getInstance()->getCampagneFromDeclarantMouvements($identifiant);
        if(!$this->campagnes) {
            $this->campagnes = array(ConfigurationClient::getInstance()->getCampagneVinicole()->getCampagneByDate(date('Y-m-d')));
        }
        $this->campagne = $request->getParameter('campagne', $this->campagnes[0]);
        $this->mouvements = MouvementLotHistoryView::getInstance()->getMouvementsByDeclarant($identifiant, $this->campagne)->rows;

        uasort($this->mouvements, function($a, $b) { if($a->value->date ==  $b->value->date) { return $a->value->numero_archive < $b->value->numero_archive; } return $a->value->date < $b->value->date; });

        $this->syntheseLots = LotsClient::getInstance()->getSyntheseLots($identifiant, $this->campagne);
    }

    public function executeManquements(sfWebRequest $request) {
      $this->chgtDenoms = [];
      $this->campagne = $request->getParameter('campagne', null);
      $this->manquements = DegustationClient::getInstance()->getManquements($this->campagne);
    }

    public function executeElevages(sfWebRequest $request) {
      $this->lotsElevages = DegustationClient::getInstance()->getElevages($request->getParameter('campagne'));
    }

    public function executeRedeguster(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $doc = DegustationClient::getInstance()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        $this->forward404Unless($lot);

        $lot->redegustation();

        $doc->generateMouvementsLots();
        $doc->save();

        return $this->redirect("degustation_lot_historique", array('identifiant' => $lot->declarant_identifiant, 'unique_id'=> $lot->unique_id));
    }

    public function executeRecoursOc(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $doc = DegustationClient::getInstance()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        $this->forward404Unless($lot);

        $lot->recoursOc();

        $doc->generateMouvementsLots();
        $doc->save();

        return $this->redirect("degustation_lot_historique", array('identifiant' => $lot->declarant_identifiant, 'unique_id'=> $lot->unique_id));
    }

    public function executeLotConformeAppel(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $doc = DegustationClient::getInstance()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        $this->forward404Unless($lot);

        $lot->conformeAppel();

        $doc->generateMouvementsLots();
        $doc->save();

        return $this->redirect("degustation_lot_historique", array('identifiant' => $lot->declarant_identifiant, 'unique_id'=> $lot->unique_id));
    }

    public function executeLotLeverNonConformite(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $doc = DegustationClient::getInstance()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        $this->forward404Unless($lot);

        $lot->leverNonConformite();

        $doc->generateMouvementsLots();
        $doc->save();

        return $this->redirect("degustation_lot_historique", array('identifiant' => $lot->declarant_identifiant, 'unique_id'=> $lot->unique_id));
    }

    public function executeLotReputeConforme(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $unique_id = $request->getParameter('unique_id');
        $doc = acCouchdbManager::getClient()->find($docid);
        $lot = $doc->getLot($unique_id);
        if (!$lot->getMouvement(Lot::STATUT_AFFECTABLE)) {
            throw new sfException("Action impossible");
        }
        $lot->affectable = false;
        $doc->save();
        return $this->redirect("degustation_lot_historique", array('identifiant' => $lot->declarant_identifiant, 'unique_id'=> $lot->unique_id));
    }

    public function executeLotAffectable(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $unique_id = $request->getParameter('unique_id');
        $doc = acCouchdbManager::getClient()->find($docid);
        $lot = $doc->getLot($unique_id);
        if (!$lot->getMouvement(Lot::STATUT_NONAFFECTABLE)) {
            throw new sfException("Action impossible");
        }
        $lot->affectable = true;
        $doc->save();
        return $this->redirect("degustation_lot_historique", array('identifiant' => $lot->declarant_identifiant, 'unique_id'=> $lot->unique_id));
    }

    public function executeLotAffectation(sfWebRequest $request){
      $identifiant = $request->getParameter('id');
      $unique_id = $request->getParameter('unique_id');
      $this->lot = LotsClient::getInstance()->findByUniqueId($identifiant, $unique_id);
      $this->etablissement = EtablissementClient::getInstance()->find($identifiant);

      $this->form = new DegustationAffectionLotForm($this->lot, !$request->getParameter('plusdedegustations', false));

      if (!$request->isMethod(sfWebRequest::POST)) {

          return sfView::SUCCESS;
      }

      $this->form->bind($request->getParameter($this->form->getName()));

      if (!$this->form->isValid()) {

          return sfView::SUCCESS;

      }

      $this->form->save();

      $degust = $this->form->getDegustation();

      if ($degust->etape == DegustationEtapes::ETAPE_PRELEVEMENTS ) {
        $this->getUser()->setFlash("warning", "La dégustation est à l'étape du prélèvement, votre numéro de table ne sera pas pris en compte.");
      }

      if (in_array($degust->etape,array(DegustationEtapes::ETAPE_CONVOCATIONS,DegustationEtapes::ETAPE_DEGUSTATEURS,DegustationEtapes::ETAPE_LOTS)) ) {
        $this->getUser()->setFlash("warning", "La dégustation est à l'étape de l'enregistrement des lots, votre statut de prélèvement et numéro de table ne sera pas pris en compte.");
      }

       return $this->redirect("degustation_lot_historique",array('identifiant' => $identifiant, 'unique_id'=> $unique_id));

    }

    public function executeAnonymize(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $degustation->anonymize();
      $degustation->save();
      return $this->redirect('degustation_commission_etape', $degustation);
    }

    public function executeDesanonymize(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $degustation->desanonymize();
      $degustation->storeEtape(DegustationEtapes::ETAPE_ANONYMATS);
      $degustation->save();
      return $this->redirect('degustation_anonymats_etape', $degustation);
    }

    public function executeMailPrevisualisation(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->identifiant_operateur = $request->getParameter('identifiant');
      $this->lotsOperateur = $this->degustation->getLotsByOperateurs($this->identifiant_operateur);

      $this->popup = true;

      $this->setTemplate('notificationsEtape');
    }

    public function executeSetEnvoiMail(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();

      $identifiant = $request->getParameter('identifiant');
      $mailto = $identifiant;
      $date = $request->getParameter('envoye', date('Y-m-d H:i:s'));

      if(!boolval($date)) {
          $date = null;
          $mailto = null;
      }

      $this->degustation->setMailEnvoyeEtablissement($identifiant, $date);
      $this->degustation->save(false);

      if ($mailto) {
          return $this->redirect('degustation_notifications_etape', array('id' => $this->degustation->_id, 'mail_to_identifiant' => $identifiant));
      } else {
          return $this->redirect('degustation_notifications_etape', $this->degustation);
      }
    }

    public function executeMailToNotification(sfWebRequest $request) {
        $degustation = $this->getRoute()->getDegustation();
        $identifiant = $request->getParameter('identifiant');
        $lots = $degustation->getLotsByOperateurs()[$identifiant];
        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Date', 'Partial'));

        $lotsConformes = [];
        $lotsNonConformes = [];

        foreach ($lots as $lot) {
            switch ($lot->statut) {
                case Lot::STATUT_CONFORME:
                    $lotsConformes[] = $lot;
                    break;
                case Lot::STATUT_NONCONFORME:
                    $lotsNonConformes[] = $lot;
                    break;
            }
        }

        $email = EtablissementClient::getInstance()->find($identifiant)->getEmail();
        $email = trim($email);

        $cc = Organisme::getInstance(null, 'degustation')->getEmail();
        $subject = sprintf("Résultat de la dégustation du %s", $degustation->getDateFormat('d/m/Y'));
        $body = html_entity_decode(str_replace("\n", "%0A", strip_tags(get_partial('degustation/notificationEmail', [
            'degustation' => $degustation,
            'identifiant' => $identifiant,
            'lotsConformes' => $lotsConformes,
            'lotsNonConformes' => $lotsNonConformes
        ]))), ENT_QUOTES | ENT_XML1, 'UTF-8');

        $mailto = "mailto:$email?cc=$cc&subject=$subject&body=$body";
        $mailto = mb_strcut($mailto, 0, 1600); // Chrome limite les mailto à un certain nombre de caractères 1600 semblent être le max

        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setStatusCode(302);
        $this->getResponse()->setHttpHeader('Location', $mailto);
        $this->getResponse()->setContent(sprintf('<html><head><meta http-equiv="refresh" content="%d;url=%s"/></head></html>', 0, $mailto));
        $this->getResponse()->send();

        throw new sfStopException();
    }

    public function executeTriTable(sfWebRequest $request) {
        $degustation = $this->getRoute()->getDegustation();
        $numero_table = $request->getParameter('numero_table');
        $this->triTableForm = new DegustationTriTableForm(array());

        if (!$request->isMethod(sfWebRequest::POST)) {
            return $this->redirect('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table));
        }

        $this->triTableForm->bind($request->getParameter($this->triTableForm->getName()));
        $recap = $this->triTableForm->getValue('recap');

        if (!$this->triTableForm->isValid()) {
            if($recap) {
                return $this->redirect('degustation_organisation_table_recap', array('id' => $degustation->_id));
            }
            return $this->redirect('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table));
        }

        $values = $this->triTableForm->getValues();
        unset($values['recap']);

        $degustation->tri = join('|', array_filter(array_unique(array_values($values))));
        $degustation->save();

        if($recap) {
            return $this->redirect('degustation_organisation_table_recap', array('id' => $degustation->_id));
        }
        return $this->redirect('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table));
    }

    public function executeEtiquettesPrlvmtCsv(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
      $this->getResponse()->setHttpHeader('Content-Type', 'text/csv; charset=ISO-8859-1');
      $this->setLayout(false);
    }

    public function executeEtiquettesPrlvmtPdf(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
      $this->isEtiquette = true;
      $this->document = new ExportDegustationEtiquettesPrlvmtPDF($this->degustation, $request->getParameter('identifiant', null), $request->getParameter('anonymat4labo', false), $request->getParameter('output', 'pdf'), false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeEtiquettesTablesEchantillonsAnonymesPDF(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->isEtiquette = true;
      $this->document = new ExportDegustationEtiquettesTablesEchantillonsParAnonymatOrUniqueidPDF($this->degustation, $request->getParameter('output', 'pdf'), $request->getParameter('tri', 'numero_anonymat'), false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheIndividuellePDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->document = new ExportDegustationFicheIndividuellePDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheTablesEchantillonsParDossierPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->document = new ExportDegustationFicheTablesEchantillonsParDossierPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheTablesEchantillonsParAnonymatPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheTablesEchantillonsParAnonymatPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeDegustationAllNotificationsPDF(sfWebRequest $request)
    {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsNotAnonymized();
        $this->document = new ExportDegustationAllNotificationsPDF($this->degustation, $request->getParameter('output', 'pdf'), false);
        return $this->mutualExcecutePDF($request);
    }

    public function executeDegustationConformitePDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $etablissement = EtablissementClient::getInstance()->findByIdentifiant($request->getParameter('identifiant'));
      $this->forward404Unless(
            $this->getUser()->isAdmin() ||
            $this->getUser()->isStalker() ||
            $request->getParameter('action') != 'degustationConformitePDF' ||
            $this->getUser()->getCompte()->getSociete()->identifiant == $etablissement->getSociete()->identifiant
      );
      $this->document = new ExportDegustationConformitePDF($this->degustation,$etablissement,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeDegustationNonConformitePDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $lot_dossier = $request->getParameter('lot_dossier');
      $lot_archive = $request->getParameter('lot_archive');
      $lot = $this->degustation->getLotByNumDossierNumArchive($lot_dossier, $lot_archive);
      $this->forward404Unless(
          $this->getUser()->isAdmin() ||
          $this->getUser()->isStalker() ||
          $request->getParameter('action') != 'degustationNonConformitePDF' ||
          strpos($lot->declarant_identifiant, $this->getUser()->getCompte()->getSociete()->identifiant) === 0
      );
      $this->document = new ExportDegustationNonConformitePDF($this->degustation,$lot, $request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheRecapTablesPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->document = new ExportDegustationFicheRecapTablesPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeRetraitNonConformitePDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $etablissement = EtablissementClient::getInstance()->findByIdentifiant($request->getParameter('identifiant'));
      $lot_dossier = $request->getParameter('lot_dossier');
      $this->document = new ExportRetraitNonConformitePDF($degustation,$etablissement,$lot_dossier,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheLotsAPreleverPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheLotsAPreleverPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheIndividuelleLotsAPreleverPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheIndividuelleLotsAPreleverPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFichePresenceDegustateursPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->document = new ExportDegustationFichePresenceDegustateursPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeProcesVerbalDegustationPDF(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $this->redirectIfIsNotAnonymized();
      $this->document = new ExportDegustationFicheProcesVerbalDegustationPDF($this->degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    private function mutualExcecutePDF(sfWebRequest $request) {
        $this->document->setPartialFunction(array($this, 'getPartial'));
        if ($request->getParameter('force')) {
            $this->document->removeCache();
        }
        $this->document->generate();
        $this->document->addHeaders($this->getResponse());

        if(isset($this->isEtiquette) && sfConfig::get('sf_debug')) {
            $temp = tmpfile();
            fwrite($temp, $this->document->output());
            shell_exec(sprintf("pdftk %s background %s output %s", stream_get_meta_data($temp)['uri'], sfConfig::get('sf_web_dir')."/images/pdf/etiquettes_70x36.pdf", stream_get_meta_data($temp)['uri'].".pdf"));
            $pdfContent = file_get_contents(stream_get_meta_data($temp)['uri'].".pdf");
            $this->getResponse()->setHttpHeader('Content-Length', filesize(stream_get_meta_data($temp)['uri'].".pdf"));
            unlink(stream_get_meta_data($temp)['uri'].".pdf");
            fclose($temp);
            return $this->renderText($pdfContent);
        }


        return $this->renderText($this->document->output());
    }


    private function redirectIfIsAnonymized()
    {
        if (DegustationConfiguration::getInstance()->isAnonymisationManuelle()) {
            return false;
        }

        if ($this->degustation->isAnonymized()) {
            $etape = $this->getRouteEtape($this->degustation->etape);

            if (DegustationEtapes::$etapes[$this->degustation->etape] < DegustationEtapes::$etapes[DegustationEtapes::ETAPE_ANONYMATS]) {
                return $this->redirect($this->getRouteEtape(DegustationEtapes::ETAPE_ANONYMATS), $this->degustation);
            } else {
                return $this->redirect($etape, $this->degustation);
            }
        }
    }

    private function redirectIfIsNotAnonymized()
    {
        if (DegustationConfiguration::getInstance()->isAnonymisationManuelle()) {
            return false;
        }

        if (! $this->degustation->isAnonymized()) {
            $etape = $this->getRouteEtape($this->degustation->etape);

            if (DegustationEtapes::$etapes[$this->degustation->etape] > DegustationEtapes::$etapes[DegustationEtapes::ETAPE_ANONYMATS]) {
                return $this->redirect($this->getRouteEtape(DegustationEtapes::ETAPE_ANONYMATS), $this->degustation);
            } else {
                return $this->redirect($etape, $this->degustation);
            }
        }
    }

    public function executeGetCourrierWithAuth(sfWebRequest $request) {
        // Gestion du cas ou le mailer ne retire pas le ">" à la fin du lien
        $request->setParameter('lot_archive', str_replace('>', '', $request->getParameter('lot_archive', null)));

        $authKey = $request->getParameter('auth');
        $degustation_id = "DEGUSTATION-".str_replace("DEGUSTATION-", "", $request->getParameter('id'));
        $identifiant = $request->getParameter('identifiant', null);
        $identifiant = str_replace(array('>', '%3E', '%3e'), '', $identifiant);
        $lot_dossier = $request->getParameter('lot_dossier', null);
        $lot_archive = $request->getParameter('lot_archive', null);
        $type = $request->getParameter('type', null);

        if (! $type) {
            throw new sfException('Parametre type n\'est pas défini');
        }

        $descriminant = '';
        switch ($type) {
            case 'NonConformite':
                if (! $lot_archive || ! $lot_dossier) { throw new sfException("Identifiant de lot manquant"); }
                $discriminant = $lot_dossier.$lot_archive;
                break;

            case 'Conformite':
                if (! $identifiant) { throw new sfException("Identifiant de compte manquant"); }
                $discriminant = $identifiant;
                break;

            default:
                break;
        }

        if (empty($discriminant)) {
            throw new sfException('Discriminant vide');
        }

        if (!UrlSecurity::verifyAuthKey($authKey, $degustation_id, $discriminant)) {
            throw new sfError403Exception("Vous n'avez pas le droit d'accéder à cette page");
        }

        if($type == "Conformite") {

            return $this->executeDegustationConformitePDF($request);
        }

        if($type == "NonConformite") {

            return $this->executeDegustationNonConformitePDF($request);
        }

        throw new sfError404Exception();
    }

    public function executeConvocationReponse(sfWebRequest $request)
    {
        $this->degustation = $request->getParameter('id');
        $this->college = $request->getParameter('college');
        $this->identifiant = $request->getParameter('identifiant');
        $authkey = $request->getParameter('auth');

        if (! UrlSecurity::verifyAuthKey($authkey, $this->degustation, $this->identifiant)) {
            throw new sfError403Exception("Vous n'avez pas le droit d'accéder à cette page");
        }

        $this->degustation = DegustationClient::getInstance()->find($this->degustation);

        if (! $this->degustation->degustateurs->exist($this->college) || ! $this->degustation->degustateurs->get($this->college)->exist($this->identifiant)) {
            throw new sfException("Vous n'êtes pas convoqué dans cette dégustation");
        }

        if ($this->degustation->degustateurs->get($this->college)->get($this->identifiant)->exist('confirmation')) {
            $presence = $this->degustation->degustateurs->get($this->college)->get($this->identifiant)->confirmation;
            return $this->redirect('degustation_convocation_auth', [
                'id' => $this->degustation->_id,
                'college' => $this->college,
                'identifiant' => $this->identifiant,
                'auth' => $authkey,
                'presence' => ($presence) ? 1 : 0
            ]);
        }

        $this->setLayout(false);
    }

    public function executeConvocationWithAuth(sfWebRequest $request) {
        $authKey = $request->getParameter('auth');
        $this->id = $request->getParameter('id');
        $this->identifiant = $request->getParameter('identifiant', null);
        $this->college = $request->getParameter('college', null);
        $this->presence = $request->getParameter('presence', null);

        if (! $this->identifiant) {
            throw new sfException('Parametre identifiant n\'est pas défini');
        }

        if (! $this->college) {
            throw new sfException('Parametre college n\'est pas défini');
        }

        if (is_null($this->presence)) {
            throw new sfException('Parametre présence n\'est pas défini');
        }

        if (!UrlSecurity::verifyAuthKey($authKey, $this->id, $this->identifiant)) {
            throw new sfError403Exception("Vous n'avez pas le droit d'accéder à cette page");
        }

        $this->setLayout(false);
        $this->degustation = DegustationClient::getInstance()->find($this->id);
        $degustateurs = $this->degustation->get("degustateurs");
        if (! $degustateurs->exist($this->college) || !$degustateurs->get($this->college)->exist($this->identifiant)) {
            throw new sfException("Le dégustateur $this->college n\'est pas dans la dégustation ");
        }
        $degustateur = $degustateurs->get($this->college)->get($this->identifiant);

        $previousPresence = null;
        if($degustateurs->get($this->college)->get($this->identifiant)->exist('confirmation')) {
            $previousPresence = $degustateurs->get($this->college)->get($this->identifiant)->get('confirmation');
        }

        $degustateurs->get($this->college)->get($this->identifiant)->add('confirmation', boolval($this->presence));

        $this->degustation->save(false);

        $this->emailSended = false;
        if($previousPresence === $degustateurs->get($this->college)->get($this->identifiant)->get('confirmation')) {

            return sfView::SUCCESS;
        }

        Email::getInstance()->sendActionDegustateurAuthMail($this->degustation, $degustateur, boolval($this->presence));
        $this->emailSended = true;
    }

    public function executeRetirerLot(sfWebRequest $request) {
        $declarant_id = $request->getParameter('id');
        $unique_id = $request->getParameter('unique_id');
        $degustation_id = $request->getParameter('degustation_id');

        $degustation = DegustationClient::getInstance()->find($degustation_id);
        $this->forward404Unless($degustation);

        $lot = $degustation->getLot($unique_id);
        $this->forward404Unless($lot);

        $degustation->removeLot($lot);
        $degustation->save();
        return $this->redirect('degustation_lot_historique', array('identifiant' => $declarant_id, 'unique_id' => $unique_id));

    }
}
