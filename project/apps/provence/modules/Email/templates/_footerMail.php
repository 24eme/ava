<?php echo Organisme::getInstance()->getNom() ?>

--
mailto:<?php echo (isset($email)) ? $email : Organisme::getInstance()->getEmail(); ?>
