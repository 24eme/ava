<?php
class ConditionnementLotForm extends acCouchdbObjectForm
{
    const NBCEPAGES = 5;

    public function __construct(acCouchdbJson $object, $options = array(), $CSRFSecret = null) {
        parent::__construct($object, $options, $CSRFSecret);
        $this->getDocable()->remove();
        $this->getValidatorSchema()->setOption('allow_extra_fields', true);
    }

    protected function updateDefaultsFromObject() {
        parent::updateDefaultsFromObject();

        $this->setDefault('destination_date', $this->getObject()->getDestinationDateFr());
        $cepages = array();
        $i=0;
        foreach($this->getObject()->cepages as $cepage => $repartition) {
            $this->setDefault('cepage_'.$i, $cepage);
            $this->setDefault('repartition_'.$i, $repartition);
            $i++;
        }
    }

    public function configure() {
        $produits = $this->getProduits();
        $cepages = $this->getCepages();

        $this->setWidget('volume', new bsWidgetFormInputFloat());
        $this->setValidator('volume', new sfValidatorNumber(array('required' => false)));

        $this->setWidget('millesime', new bsWidgetFormInput());
        $this->setValidator('millesime', new sfValidatorInteger(array('required' => false)));

        $this->setWidget('numero', new bsWidgetFormInput());
        $this->setValidator('numero', new sfValidatorString(array('required' => false)));

        $this->setWidget('destination_date', new bsWidgetFormInput());
        $this->setValidator('destination_date', new sfValidatorDate(
            array('date_output' => 'Y-m-d',
            'date_format' => '~(?P<day>\d{2})/(?P<month>\d{2})/(?P<year>\d{4})~',
            'required' => false)));

        $this->setWidget('produit_hash', new bsWidgetFormChoice(array('choices' => $produits)));
        $this->setValidator('produit_hash', new sfValidatorChoice(array('required' => false, 'choices' => array_keys($produits))));

        $this->setWidget('destination_type', new bsWidgetFormChoice(array('choices' => $this->getDestinationsType())));
        $this->setValidator('destination_type', new sfValidatorChoice(array('required' => false, 'choices' => array_keys($this->getDestinationsType()))));

        if(DRevConfiguration::getInstance()->hasSpecificiteLot()){
          $this->setWidget('specificite', new bsWidgetFormChoice(array('choices' => $this->getSpecificites())));
          $this->setValidator('specificite', new sfValidatorChoice(array('required' => false, 'choices' => array_keys($this->getSpecificites()))));
        }
        for($i = 0; $i < self::NBCEPAGES; $i++) {
            if ($cepages && count($cepages)) {
                $this->setWidget('cepage_'.$i, new bsWidgetFormChoice(array('choices' => $cepages)));
                $this->setValidator('cepage_'.$i, new sfValidatorChoice(array('required' => false, 'choices' => array_keys($cepages))));
            }else{
                $this->setWidget('cepage_'.$i, new bsWidgetFormInput());
                $this->setValidator('cepage_'.$i, new sfValidatorString(array('required' => false)));
            }
            $this->setWidget('repartition_'.$i, new bsWidgetFormInputFloat([], ['class' => 'form-control text-right input-float input-hl']));
            $this->setValidator('repartition_'.$i, new sfValidatorNumber(array('required' => false)));
        }

        $this->setWidget('elevage', new sfWidgetFormInputCheckbox());
        $this->setValidator('elevage', new sfValidatorBoolean(['required' => false]));

        $this->widgetSchema->setNameFormat('[%s]');
    }

    public function doUpdateObject($values) {
        parent::doUpdateObject($values);

        $this->getObject()->remove('cepages');
        $this->getObject()->add('cepages');
        for($i = 0; $i < self::NBCEPAGES; $i++) {
            if(!$values['cepage_'.$i] || !$values['repartition_'.$i]) {
                continue;
            }

            $this->getObject()->addCepage($values['cepage_'.$i], $values['repartition_'.$i]);
        }
        if (!empty($values['elevage'])) {
          $this->getObject()->statut = Lot::STATUT_ELEVAGE;
        }
    }

    public function getDestinationsType()
    {
        return array_merge(array("" => ""), DRevClient::$lotDestinationsType);
    }

    public function getSpecificites()
    {
        return array_merge(array("" => ""), DRevConfiguration::getInstance()->getSpecificites());
    }

    public function getProduits()
    {
        $produits = array();
        foreach ($this->getObject()->getDocument()->getConfigProduits() as $produit) {
            if(!$produit->isRevendicationParLots()) {
                continue;
            }
            if (!$produit->isActif()) {
                continue;
            }
            $produits[$produit->getHash()] = $produit->getLibelleComplet();
        }
        return array_merge(array('' => ''), $produits);
    }

    public function getCepages()
    {
        return array_merge(array('' => ''), $this->getObject()->getDocument()->getConfiguration()->getCepagesAutorises());
    }

}
