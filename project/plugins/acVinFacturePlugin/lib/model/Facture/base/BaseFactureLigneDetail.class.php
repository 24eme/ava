<?php
/**
 * BaseFactureLigne
 *

 */

abstract class BaseFactureLigneDetail extends acCouchdbDocumentTree {

    public function configureTree() {
       $this->_root_class_name = 'Facture';
       $this->_tree_class_name = 'FactureLigneDetail';
    }

}