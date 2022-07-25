<?php

class DRValidation extends DocumentValidation
{
    private $configuration;

    public function __construct($document, $options = null)
    {
        $this->configuration = $options['configuration'] ?? ConfigurationClient::getInstance()->getCurrent();
        parent::__construct($document, $options);
    }

    public function configure()
    {
        $this->addControle(self::TYPE_WARNING, 'rendement_manquant', "Rendement non présent en configuration");
        $this->addControle(self::TYPE_WARNING, 'rendement_ligne_manquante', "Il manque une ligne dans le produit");
        $this->addControle(self::TYPE_WARNING, 'rendement_declaration', "Le rendement n'est pas respecté");
    }

    public function controle()
    {
        foreach ($this->document->getProduits() as $produit) {
            $this->controleRendement($produit);
        }
    }

    public function controleRendement($produit)
    {
        $produit_conf = $this->configuration->declaration->get($produit['hash']);

        if (! $produit_conf->hasRendement()) {
            $this->addPoint(self::TYPE_WARNING, 'rendement_manquant', "Il n'y a pas de rendement pour le produit : ".$produit['libelle']);
            return 0;
        }

        $missing_line = false;
        if (array_key_exists('04', $produit['lignes']) === false || array_key_exists('05', $produit['lignes']) === false) {
            $this->addPoint(self::TYPE_WARNING, 'rendement_ligne_manquante', "Il manque une ligne pour le calcul du rendement L5 : <strong>".$produit['libelle']."</strong>");
            $missing_line = true;
        }

        if (array_key_exists('04', $produit['lignes']) === false || array_key_exists('15', $produit['lignes']) === false) {
            $this->addPoint(self::TYPE_WARNING, 'rendement_ligne_manquante', "Il manque une ligne pour le calcul du rendement L15 : <strong>".$produit['libelle']."</strong>");
            $missing_line = true;
        }

        if ($missing_line) {
            return 0;
        }

        if (round($produit['lignes']['05']['val'] / $produit['lignes']['04']['val'], 2) > $produit_conf->getRendementDrL5()) {
            $this->addPoint(
                self::TYPE_WARNING,
                'rendement_declaration',
                "Le rendement L5 du produit <strong>".$produit['libelle']."</strong> est de " . round($produit['lignes']['05']['val'] / $produit['lignes']['04']['val'], 2) . " hl/ha, " ."le maximum étant <strong>".$produit_conf->getRendementDrL5()."</strong> hl/ha"
            );
        }

        if (round($produit['lignes']['15']['val'] / $produit['lignes']['04']['val'], 2) > $produit_conf->getRendementDrL15()) {
            $this->addPoint(
                self::TYPE_WARNING,
                'rendement_declaration',
                "Le rendement L15 du produit <strong>".$produit['libelle']."</strong> est de " . round($produit['lignes']['15']['val'] / $produit['lignes']['04']['val'], 2) . " hl/ha, " ."le maximum étant <strong>".$produit_conf->getRendementDrL15()."</strong> hl/ha"
            );
        }
    }
}