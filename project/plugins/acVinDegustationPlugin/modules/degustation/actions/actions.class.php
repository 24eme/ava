<?php

class degustationActions extends sfActions {

    public function executeIndex(sfWebRequest $request) {
        $this->form = new DegustationCreationForm(new Degustation());

        $this->degustations = DegustationClient::getInstance()->getHistory();

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $degustation = $this->form->save();

        return $this->redirect('degustation_redirect', $degustation);
    }

    public function executePrelevementLots(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsValidee();

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

    public function executeSelectionDegustateurs(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsValidee();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_DEGUSTATEURS))) {
            $this->degustation->save();
        }

        $this->form = new DegustationSelectionDegustateursForm($this->degustation);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->save();

        return ($next = $this->getRouteNextEtape(DegustationEtapes::ETAPE_DEGUSTATEURS))? $this->redirect($next, $this->degustation) : $this->redirect('degustation');
    }

    public function executeValidation(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsValidee();
        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_VALIDATION))) {
            $this->degustation->save();
        }

        $this->form = new DegustationValidationForm($this->degustation);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->save();

        return $this->redirect('degustation_visualisation', array('id' => $this->degustation->_id));
    }


    public function executeVisualisation(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
    }

    public function executeDegustateurConfirmation(sfWebRequest $request) {
      $this->degustation = $this->getRoute()->getDegustation();
      $this->degustateurHash = $request->getParameter('degustateurHash',null);
      $this->confirmation = $request->getParameter('confirmation',null);
      $this->degustation->getOrAdd($this->degustateurHash)->add('confirmation',boolval($this->confirmation));
      $this->degustation->save();
      return $this->redirect('degustation_visualisation', array('id' => $this->degustation->_id));
    }


    public function executeOrganisationTable(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->numero_table = $request->getParameter('numero_table',0);


        $this->liste_tables = $this->degustation->getTablesWithFreeLots();
        $this->tableLots = $this->degustation->getLotsTableOrFreeLots($this->numero_table);
        $this->nb_tables = count($this->liste_tables);
        $options = array('tableLots' => $this->tableLots, 'numero_table' => $this->numero_table, 'liste_tables' => $this->liste_tables);
        $this->form = new DegustationOrganisationTableForm($this->degustation, $options);
        $this->ajoutLeurreForm = new DegustationAjoutLeurreForm($this->degustation);


        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }
        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }
        $this->form->save();

        return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table));
    }

    public function executeAjoutLeurre(sfWebRequest $request){
        $this->degustation = $this->getRoute()->getDegustation();
        $this->ajoutLeurreForm = new DegustationAjoutLeurreForm($this->degustation);
        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }
        $this->ajoutLeurreForm->bind($request->getParameter($this->ajoutLeurreForm->getName()));

        if (!$this->ajoutLeurreForm->isValid()) {

            return sfView::SUCCESS;
        }
        $this->ajoutLeurreForm->save();
        return $this->redirect('degustation_organisation_table', array('id' => $this->degustation->_id, 'numero_table' => "0"));
    }

    public function executeResultats(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->numero_table = $request->getParameter('numero_table',0);
        if(!$this->numero_table && $this->degustation->getFirstNumeroTable()){
          return $this->redirect('degustation_resultats', array('id' => $this->degustation->_id, 'numero_table' => $this->degustation->getFirstNumeroTable()));
        }

        if ($this->degustation->storeEtape($this->getEtape($this->degustation, DegustationEtapes::ETAPE_RESULTATS))) {
            $this->degustation->save();
        }

        $this->tableLots = $this->degustation->getLotsTableOrFreeLots($this->numero_table);
        $this->nb_tables = count($this->degustation->getTablesWithFreeLots());
        $options = array('tableLots' => $this->tableLots, 'numero_table' => $this->numero_table);
        $this->form = new DegustationResultatsForm($this->degustation, $options);



        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }
        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }
        $this->form->save();

        return $this->redirect('degustation_resultats', array('id' => $this->degustation->_id, 'numero_table' => $this->numero_table));
    }

    public function executeDevalidation(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->degustation->devalidate();
        $this->degustation->save();

        $this->getUser()->setFlash("notice", "La déclaration a été dévalidé avec succès.");

        return $this->redirect('degustation_validation', $this->degustation);
    }

    public function executeRedirect(sfWebRequest $request) {
        $this->degustation = $this->getRoute()->getDegustation();
        $this->redirectIfIsValidee();
        return ($next = $this->getRouteNextEtape($this->degustation->etape))? $this->redirect($next, $this->degustation) : $this->redirect('degustation');
    }

    public function redirectIfIsValidee(){
      if ($this->degustation->isValidee()) {
          return $this->redirect('degustation_visualisation', $this->degustation);
      }
    }

    protected function getEtape($doc, $etape, $class = "DegustationEtapes") {
        $etapes = $class::getInstance();
        if (!$doc->exist('etape')) {
            return $etape;
        }
        return ($etapes->isLt($doc->etape, $etape)) ? $etape : $doc->etape;
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

    public function executeList(sfWebRequest $request) {
        $etablissement_id = $request->getParameter('id');
        $this->etablissement = EtablissementClient::getInstance()->find($etablissement_id);
        $this->forward404Unless($this->etablissement);

        $this->lots = array();
        foreach (MouvementLotView::getInstance()->getByDeclarantIdentifiant($etablissement_id)->rows as $item) {
            $key = Lot::generateMvtKey($item->value);
            if (!isset($this->lots[$key])) {
                $this->lots[$key] = $item->value;
                $this->lots[$key]->steps = array();
            }
            $this->lots[$key]->steps[] = $item->value;
        }
    }

}
