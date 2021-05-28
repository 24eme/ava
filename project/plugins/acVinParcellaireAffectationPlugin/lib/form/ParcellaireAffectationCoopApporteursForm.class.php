<?php

class ParcellaireAffectationCoopApporteursForm extends acCouchdbObjectForm {

    public function configure() {
        foreach ($this->getObject()->apporteurs as $id => $apporteur) {
            $this->setWidget($id , new WidgetFormInputCheckbox());
            $this->setValidator($id, new ValidatorBoolean());
        }
        $this->widgetSchema->setNameFormat('apporteurs[%s]');
    }

    protected function updateDefaultsFromObject() {
        parent::updateDefaultsFromObject();
        foreach ($this->getObject()->getApporteurs() as $id => $apporteur) {
          if($apporteur->apporteur){
              $defaults[$id] = true;
          }
        }
        $this->setDefaults($defaults);
    }

    protected function doUpdateObject($values) {
      parent::doUpdateObject($values);
      foreach ($this->getObject()->getApporteurs() as $id => $apporteur) {
          $apporteur->apporteur = boolval($values[$id]);
      }
      $this->getObject()->updateApporteurs();
    }

    protected function doSave($con = null) {
        $this->updateObject();
        $this->object->getCouchdbDocument()->save();
    }

}
