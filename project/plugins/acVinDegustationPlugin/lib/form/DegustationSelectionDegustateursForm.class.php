<?php

class DegustationSelectionDegustateursForm extends acCouchdbForm {

    protected $degustateurs;
    protected $college;

    public function __construct(acCouchdbDocument $doc, $defaults = array(), $options = array(), $CSRFSecret = null) {
        $doc->getOrAdd('degustateurs');
        $this->college = $options['college'];
        parent::__construct($doc, $defaults, $options, $CSRFSecret);
        $defaults = array_merge($this->getDefaults(), $this->getDefaultsByDoc($doc));
        $this->setDefaults($defaults);
    }

	public function configure()
    {
	    $form = new BaseForm();
      $subForm = new BaseForm();
      foreach($this->getDegustateursByCollege() as $compte_id => $compte) {
          $subForm->embedForm($compte->_id, new DegustationSelectionDegustateurForm());
      }
      $form->embedForm($this->college, $subForm);
      $this->embedForm('degustateurs', $form);
      $this->widgetSchema->setNameFormat('degustation[%s]');
    }

    protected function getDefaultsByDoc($doc)
    {
        $defaults = array();
        foreach($this->getDegustateursByCollege() as $compte_id => $compte) {
                $selectionne = 0;
                if ($doc->degustateurs->exist($this->college) && $doc->degustateurs->{$this->college}->exist($compte_id)) {
                    $selectionne = 1;
                }
                $defaults['degustateurs'][$this->college][$compte_id] = array('selectionne' => $selectionne);

        }
        return $defaults;
    }

	public function save() {
		$values = $this->getValues();
		$doc = $this->getDocument();
    $doc->getOrAdd('degustateurs');
    foreach ($values['degustateurs'] as $college => $items) {
        if($college == $this->college){
          $doc->degustateurs->remove($college);
        }
        foreach ($items as $compteId => $val) {
            if (isset($val['selectionne']) && !empty($val['selectionne'])) {
                $compte = $this->getCompteByIdentifiant($compteId);
                $degustateur = $doc->degustateurs->getOrAdd($college)->getOrAdd($compteId);
		$degustateur->getOrAdd('libelle');
		$degustateur->libelle = $compte->getLibelleWithAdresse();
            }
        }
    }
    $doc->save();
	}

    public function getDegustateursByCollege() {
        if (!$this->degustateurs) {
            $this->degustateurs = array();
                $comptes = CompteTagsView::getInstance()->listByTags('automatique', $this->college);
                if (count($comptes) > 0) {
                    $result = array();
                    foreach ($comptes as $compte) {
                        $result[$compte->id] = CompteClient::getInstance()->find($compte->id);
                    }
                    $this->degustateurs = $result;
                }
            ksort($this->degustateurs);
        }
        return $this->degustateurs;
    }

    public function getCompteByIdentifiant($identifiant) {
        $comptes = $this->getDegustateursByCollege();
        return (isset($comptes[$identifiant]))? $comptes[$identifiant] : null;
    }

}
