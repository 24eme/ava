<?php

class degustationActions extends sfActions {

    public function executeIndex(sfWebRequest $request) {
        $this->form = new DegustationCreationForm();
        $this->lotsPrelevables = DegustationClient::getInstance()->getLotsPrelevables();
        $this->lotsElevages = MouvementLotView::getInstance()->getByStatut(Lot::STATUT_ELEVAGE)->rows;
        $this->lotsManquements = MouvementLotView::getInstance()->getByStatut(Lot::STATUT_MANQUEMENT_EN_ATTENTE)->rows;

        $this->campagne = ConfigurationClient::getInstance()->getCampagneManager()->getCurrent();

        $this->degustations = DegustationClient::getInstance()->getHistory();

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $degustation = $this->form->save();

        return $this->redirect('degustation_prelevement_lots', $degustation);
    }

    public function executeListe(sfWebRequest $request)
    {
        $this->campagne = $request->getParameter('campagne');
        $this->degustations = DegustationClient::getInstance()->getHistory(9999, acCouchdbClient::HYDRATE_JSON);
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

    public function executePrelevementLots(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        $this->redirectIfIsAnonymized();

        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_LOTS))) {
            $this->degustation->save();
        }

        $this->form = new DegustationPrelevementLotsForm($this->degustation);

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

        return $this->redirect('degustation_prelevements_etape', $this->degustation);
    }

    public function executeUpdateLot(sfWebRequest $request)
    {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->lotkey = $request->getParameter('lot');
        $this->lot = $this->degustation->lots->get($request->getParameter('lot'));

        $this->form = new DegustationLotForm($this->lot);

        if ($request->isMethod(sfWebRequest::POST)) {
            $this->form->bind($request->getParameter($this->form->getName()));

            if ($this->form->isValid()) {
                $this->form->save();
                return $this->redirect('degustation_preleve', $this->degustation);
            }
        }
    }

    public function executeSupprimerLotNonPreleve(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->lot = $request->getParameter('lot');

        $lots = $this->degustation->lots;

        foreach ($lots as $key => $value) {
          if($this->lot <= $key && isset($this->degustation->lots[$key+1])){
            $this->degustation->lots[$key] = $this->degustation->lots[$key+1];
          }
          if(!isset($this->degustation->lots[$key+1])){
            unset($this->degustation->lots[$key]);
            break;
          }
        }


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
            $this->degustation->save();
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
          return $this->redirect('degustation_prelevements_etape', $this->degustation);
        }

        return $this->redirect('degustation_selection_degustateurs', array('id' => $this->degustation->_id ,'college' => $next_college));
    }


    public function executePrelevementsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_PRELEVEMENTS))) {
            $this->degustation->save();
        }
    }

    public function executeTablesEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsAnonymized();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_TABLES))) {
            $this->degustation->save();
        }
    }

    public function executeAnonymatsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_ANONYMATS))) {
            $this->degustation->save();
          }
    }

    public function executeCommissionEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_COMMISSION))) {
            $this->degustation->save();
          }
    }

    public function executeResultatsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->infosDegustation = $this->degustation->getInfosDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_RESULTATS))) {
            $this->degustation->save();
          }
    }

    public function executeNotificationsEtape(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_NOTIFICATIONS))) {
            $this->degustation->save();
        }
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

      if ($request->isXmlHttpRequest()) {

        return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->degustation->_id, "revision" => $this->degustation->_rev))));
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
      $this->degustation->save();

      return $this->redirect('degustation_degustateurs_confirmation', $this->degustation);

    }

    public function executeOrganisationTable(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->tri = $request->getParameter('tri');
        if(!$request->getParameter('numero_table')) {
            if(!$this->tri){
                $this->tri = 'Couleur|Genre|Appellation';
            }
            return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => 1, 'tri' => $this->tri));
        }

        $this->numero_table = $request->getParameter('numero_table');

        if (!$request->getParameter('tri')) {
            if(!$this->tri){
                $this->tri = 'Couleur|Genre|Appellation';
            }
            return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table, 'tri' => $this->tri));
        }
        $this->tri_array = explode('|', strtolower($this->tri));

        $this->syntheseLots = $this->degustation->getSyntheseLotsTableCustomTri($this->numero_table, $this->tri_array);
        $this->form = new DegustationOrganisationTableForm($this->degustation, $this->numero_table, $this->tri_array);
        $this->ajoutLeurreForm = new DegustationAjoutLeurreForm($this->degustation, array('table' => $this->numero_table));
        $this->triTableForm = new DegustationTriTableForm($this->tri_array, false);

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

    public function executeUpPositionLot(sfWebRequest $request) {
        $degustation = $this->getRoute()->getDegustation();
        $index = $request->getParameter('index');
        $tri = $request->getParameter('tri');
        $numero_table = $request->getParameter('numero_table');

        $this->forward404Unless($degustation->lots->exist($index));
        $lot = $degustation->lots->get($index);
        $lot->upPosition();
        $degustation->save();
        return $this->redirect('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table, 'tri' => $tri));
    }

    public function executeDownPositionLot(sfWebRequest $request) {
        $degustation = $this->getRoute()->getDegustation();
        $index = $request->getParameter('index');
        $tri = $request->getParameter('tri');
        $numero_table = $request->getParameter('numero_table');

        $this->forward404Unless($degustation->lots->exist($index));
        $lot = $degustation->lots->get($index);
        $lot->downPosition();
        $degustation->save();
        return $this->redirect('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table, 'tri' => $tri));
    }

    public function executeOrganisationTableRecap(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->tri = $request->getParameter('tri');
        $this->tri_array = explode('|', strtolower($this->tri));

        $this->form = new DegustationOrganisationTableRecapForm($this->degustation);
        $this->triTableForm = new DegustationTriTableForm($this->tri_array, true);

        $this->syntheseLots = $this->degustation->getSyntheseLotsTable(null);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }
        $this->form->save();

        return $this->redirect('degustation_tables_etape', $this->degustation);
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
        $this->numero_table = $request->getParameter('numero_table',0);
        $this->popup_validation = $request->getParameter('popup',0);

        if(!$this->numero_table && $this->degustation->getFirstNumeroTable()){
          return $this->redirect('degustation_resultats', array('id' => $this->degustation->_id, 'numero_table' => $this->degustation->getFirstNumeroTable()));
        }

        $this->tableLots = $this->degustation->getLotsByTable($this->numero_table);
        $this->nb_tables = count($this->degustation->getTablesWithFreeLots());
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
        $this->numero_table = $request->getParameter('numero_table',0);

        if(!$this->numero_table && $this->degustation->getFirstNumeroTable()){
          return $this->redirect('degustation_presences', array('id' => $this->degustation->_id, 'numero_table' => $this->degustation->getFirstNumeroTable()));
        }

        $this->nb_tables = count($this->degustation->getTablesWithFreeLots());
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

            return $this->redirect('degustation_prelevement_lots', $this->degustation);
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
        $etablissement_identifiant = $request->getParameter('identifiant');
        $this->campagne = $request->getParameter('campagne');
        $this->numero_dossier = $request->getParameter('numero_dossier');
        $this->numero_archive = $request->getParameter('numero_archive');
        $this->etablissement = EtablissementClient::getInstance()->findByIdentifiant($etablissement_identifiant);
        $this->mouvements =  MouvementLotHistoryView::getInstance()->getMouvements($etablissement_identifiant, $this->campagne, $this->numero_dossier,$this->numero_archive, null, null, true)->rows;
    }

    public function executeList(sfWebRequest $request) {
        $identifiant = $request->getParameter('identifiant');
        $this->etablissement = EtablissementClient::getInstance()->find($identifiant);
        $this->forward404Unless($this->etablissement);
        $this->campagne = $request->getParameter('campagne', ConfigurationClient::getInstance()->getCampagneVinicole()->getCurrent());

        $this->mouvements = MouvementLotHistoryView::getInstance()->getMouvementsByDeclarant($identifiant, $this->campagne)->rows;
    }

    public function executeLot(sfWebRequest $request) {
        $periode = $request->getParameter('periode');
        $lot_id = $request->getParameter('id');
        $this->lotsStepsHistory = array();

    }

    public function executeManquements(sfWebRequest $request) {
      $this->chgtDenoms = [];
      $this->manquements = DegustationClient::getInstance()->getManquements();
    }

    public function executeElevages(sfWebRequest $request) {
      $this->lotsElevages = MouvementLotView::getInstance()->getByStatut(null, Lot::STATUT_ELEVAGE)->rows;
    }

    public function executeRedeguster(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $back = $request->getParameter('back');
        $this->forward404Unless($back);
        $doc = acCouchdbManager::getClient()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        if (!$lot) {
          $this->forward404Unless($lot);
        }
        $lot->redegustation();
        $doc->generateMouvementsLots();
        $doc->save();
        return $this->redirect($back);
    }

    public function executeRecoursOc(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $doc = acCouchdbManager::getClient()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        if (!$lot) {
          $this->forward404Unless($lot);
        }
        $lot->recoursOc();
        $doc->generateMouvementsLots();
        $doc->save();
        return $this->redirect("degustation_manquements");
    }

    public function executeLotConformeAppel(sfWebRequest $request) {
        $docid = $request->getParameter('id');
        $lotid = $request->getParameter('lot');
        $doc = acCouchdbManager::getClient()->find($docid);
        $this->forward404Unless($doc);
        $lot = $doc->getLot($lotid);
        if (!$lot) {
          $this->forward404Unless($lot);
        }
        $lot->conformeAppel();
        $doc->generateMouvementsLots();
        $doc->save();
        return $this->redirect("degustation_manquements");
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
      $degustation->save();
      return $this->redirect('degustation_anonymats_etape', $degustation);
    }

    public function executeMailPrevisualisation(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();

      $this->identifiant_operateur = $request->getParameter('identifiant');
      $this->lotsOperateur = $this->degustation->getLotsByOperateurs($this->identifiant_operateur);

      $this->popup = true;

      $this->setTemplate('notificationsEtape');
    }

    public function executeSetEnvoiMail(sfWebRequest $request){
      $this->degustation = $this->getRoute()->getDegustation();
      $date = $request->getParameter('envoye',date('Y-m-d H:i:s'));
      if(!boolval($date)){ $date = null; }

      $this->setTemplate('notificationsEtape');
      $this->degustation->setMailEnvoyeEtablissement($request->getParameter('identifiant'),$date);
      $this->degustation->save();

      return $this->redirect('degustation_notifications_etape', $this->degustation);
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

        if($recap) {
            return $this->redirect('degustation_organisation_table_recap', array('id' => $degustation->_id, 'tri' => join('|', array_filter(array_values($values)))));
        }
        return $this->redirect('degustation_organisation_table', array('id' => $degustation->_id, 'numero_table' => $numero_table, 'tri' => join('|', array_filter(array_values($values)))));
    }

    public function executeEtiquettesPrlvmtCsv(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
      $this->getResponse()->setHttpHeader('Content-Type', 'text/csv; charset=ISO-8859-1');
      $this->setLayout(false);
    }

    public function executeEtiquettesPrlvmtPdf(sfWebRequest $request) {
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationEtiquettesPrlvmtPDF($degustation, $request->getParameter('anonymat4labo', false), $request->getParameter('output', 'pdf'), false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeEtiquettesAnonymesPDF(sfWebRequest $request) {
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationEtiquettesAnonymesPDF($degustation, $request->getParameter('output', 'pdf'), false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheIndividuellePDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheIndividuellePDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheEchantillonsPrelevesPDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheEchantillonsPrelevesPDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheEchantillonsPrelevesTablePDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheEchantillonsPrelevesTablePDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeDegustationAllNotificationsPDF(sfWebRequest $request)
    {
        $degustation = $this->getRoute()->getDegustation();
        $this->document = new ExportDegustationAllNotificationsPDF($degustation, $request->getParameter('output', 'pdf'), false);
        return $this->mutualExcecutePDF($request);
    }

    public function executeDegustationConformitePDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $etablissement = EtablissementClient::getInstance()->findByIdentifiant($request->getParameter('identifiant'));
      $this->document = new ExportDegustationConformitePDF($degustation,$etablissement,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeDegustationNonConformitePDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $lot_dossier = $request->getParameter('lot_dossier');
      $lot_archive = $request->getParameter('lot_archive');
      $lot = $degustation->getLotByNumDossierNumArchive($lot_dossier, $lot_archive);
      $this->document = new ExportDegustationNonConformitePDF($degustation,$lot, $request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheRecapTablesPDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheRecapTablesPDF($degustation,$request->getParameter('output','pdf'),false);
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
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheLotsAPreleverPDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFicheIndividuelleLotsAPreleverPDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheIndividuelleLotsAPreleverPDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeFichePresenceDegustateursPDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFichePresenceDegustateursPDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    public function executeProcesVerbalDegustationPDF(sfWebRequest $request){
      $degustation = $this->getRoute()->getDegustation();
      $this->document = new ExportDegustationFicheProcesVerbalDegustationPDF($degustation,$request->getParameter('output','pdf'),false);
      return $this->mutualExcecutePDF($request);
    }

    private function mutualExcecutePDF(sfWebRequest $request) {
        $this->document->setPartialFunction(array($this, 'getPartial'));
        if ($request->getParameter('force')) {
            $this->document->removeCache();
        }
        $this->document->generate();
        $this->document->addHeaders($this->getResponse());
        return $this->renderText($this->document->output());
    }


    private function redirectIfIsAnonymized(){
      if ($this->degustation->isAnonymized()) {
          return $this->redirect($this->getRouteEtape($this->degustation->etape),$this->degustation);
      }
    }



}
