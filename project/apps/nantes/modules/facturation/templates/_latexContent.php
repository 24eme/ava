<?php use_helper('TemplatingFacture'); ?>
<?php use_helper('Display'); ?>
\documentclass[a4paper, 10pt]{letter}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage[francais]{babel}
\usepackage[top=1cm, bottom=3cm, left=1cm, right=1cm, headheight=2cm, headsep=0mm, marginparwidth=0cm]{geometry}
\usepackage{fancyhdr}
\usepackage{graphicx}
\usepackage[table]{xcolor}
\usepackage{units}
\usepackage{fp}
\usepackage{tikz}
\usepackage{array}
\usepackage{multicol}
\usepackage{textcomp}
\usepackage{marvosym}
\usepackage{lastpage}
\usepackage{truncate}
\usepackage{colortbl}
\usepackage{tabularx}
\usepackage{multirow}
\usepackage{hhline}
\usepackage{longfbox}

\definecolor{noir}{rgb}{0,0,0}
\definecolor{blanc}{rgb}{1,1,1}
\definecolor{verttresclair}{rgb}{0.90,0.90,0.90}
\definecolor{vertclair}{rgb}{0.70,0.70,0.70}
\definecolor{vertfonce}{rgb}{0.17,0.29,0.28}
\definecolor{vertmedium}{rgb}{0.63,0.73,0.22}

\def\LOGO{<?php echo sfConfig::get('sf_web_dir'); ?>/images/logo_nantes_complet.png}
\def\TYPEFACTURE{<?php if($facture->isAvoir()): ?>Avoir<?php else:?>Relevé de Cotisations<?php endif; ?>}
\def\NUMFACTURE{<?php echo $facture->numero_ava; ?>}
\def\NUMADHERENT{<?php echo $facture->numero_adherent; ?>}
\def\CAMPAGNE{<?php echo ($facture->getCampageTemplate() + 1).""; ?>}
\def\EMETTEURLIBELLE{<?php echo $facture->emetteur->service_facturation; ?>}
\def\EMETTEURADRESSE{<?php echo $facture->emetteur->adresse; ?>}
\def\EMETTEURCP{<?php echo $facture->emetteur->code_postal; ?>}
\def\EMETTEURVILLE{<?php echo $facture->emetteur->ville; ?>}
\def\EMETTEURCONTACT{<?php echo $facture->emetteur->telephone; ?>}
\def\EMETTEUREMAIL{<?php echo $facture->emetteur->email; ?>}
\def\FACTUREDATE{<?php $date = new DateTime($facture->date_facturation); echo $date->format('d/m/Y'); ?>}
\def\FACTUREDECLARANTRS{<?php echo wordwrap(escape_string_for_latex($facture->declarant->raison_sociale), 35, "\\\\\hspace{1.8cm}"); ?>}
\def\FACTUREDECLARANTCVI{<?php echo $facture->getCvi(); ?>}
\def\FACTUREDECLARANTADRESSE{<?php echo wordwrap(escape_string_for_latex($facture->declarant->adresse), 35, "\\\\\hspace{1.8cm}"); ?>}
\def\FACTUREDECLARANTCP{<?php echo $facture->declarant->code_postal; ?>}
\def\FACTUREDECLARANTCOMMUNE{<?php echo $facture->declarant->commune; ?>}
\def\FACTURETOTALHT{<?php echo formatFloat($facture->total_ht, ','); ?>}
\def\FACTURETOTALTVA{<?php echo formatFloat($facture->total_taxe, ','); ?>}
\def\FACTURETOTALTTC{<?php echo formatFloat($facture->total_ttc, ','); ?>}

\pagestyle{fancy}
\renewcommand{\headrulewidth}{0cm}
\renewcommand\sfdefault{phv}
\renewcommand{\familydefault}{\sfdefault}
\fancyhead[L]{}
\fancyhead[R]{

}
\cfoot{\small{
	\EMETTEURLIBELLE \\
	\EMETTEURADRESSE - \EMETTEURCP~\EMETTEURVILLE \\
	\EMETTEURCONTACT - \EMETTEUREMAIL \\
	N°TVA : FR96803741834
}}

\begin{document}

\begin{minipage}{0.5\textwidth}
	\begin{center}
	\hspace{-1.2cm}
	\includegraphics[width=4cm]{\LOGO}
	\end{center}
\end{minipage}
\begin{minipage}{0.5\textwidth}
\lfbox[
  border-width=0.05cm,
  border-color=black,
  border-style=solid,
  width=8.9cm,
  padding={0.2cm,0.2cm},
  text-align=center
]{\textbf{\LARGE{\TYPEFACTURE}}}}

\\\vspace{12mm}

\renewcommand{\arraystretch}{1.5}
\arrayrulecolor{vertclair}
\begin{tabular}{|>{\raggedleft}m{1.0cm}|>{\centering}m{2.8cm}|>{\raggedleft}m{1.0cm}|>{\centering}m{2.8cm}|}
\hhline{|-|-|-|-|}
 \cellcolor{verttresclair} \textbf{N° :} & <?php echo $facture->numero_facture; ?> & \cellcolor{verttresclair} \textbf{Date :} & <?php $date = new DateTime($facture->date_facturation); echo $date->format('d/m/Y'); ?>  \tabularnewline
 \hhline{|-|-|-|-|}
\end{tabular}

\\\vspace{6mm}

\renewcommand{\arraystretch}{1.5}
\arrayrulecolor{vertclair}
\begin{tabular}{|>{\raggedleft}m{1.0cm}|>{\raggedright}m{7.5cm}|}
\hhline{|-|-|}
\cellcolor{verttresclair} \textbf{CVI :} & \hspace{0.3cm} \FACTUREDECLARANTCVI \tabularnewline
\hhline{|-|-|}
\end{tabular}

\\\vspace{2mm}

\renewcommand{\arraystretch}{1.5}
\arrayrulecolor{vertclair}
\begin{tabular}{|m{8.95cm}|}
\hhline{|-|}
\FACTUREDECLARANTRS \tabularnewline
\FACTUREDECLARANTADRESSE \tabularnewline
\FACTUREDECLARANTCP~\FACTUREDECLARANTCOMMUNE \tabularnewline
\hhline{|-|}
\end{tabular}
\end{minipage}

\\\vspace{4mm}

\begin{center}
\renewcommand{\arraystretch}{1.5}
\arrayrulecolor{vertclair}
\begin{tabular}{|m{9.1cm}|>{\raggedleft}m{1.5cm}|>{\raggedleft}m{2.1cm}|>{\raggedleft}m{1.9cm}|>{\raggedleft}m{2.2cm}|}
  \hline
  \rowcolor{verttresclair} \textbf{Désignation} & \textbf{Prix~uni.} & \textbf{Quantité} & \textbf{TVA} & \textbf{Total HT}  \tabularnewline
  \hline
  <?php foreach ($facture->lignes as $ligne): ?>
  	<?php foreach ($ligne->details as $detail): ?>
            <?php if ($detail->exist('quantite') && $detail->quantite === 0) {continue;} ?>
            <?php echo $ligne->libelle; ?> <?php echo $detail->libelle; ?> & {<?php echo formatFloat($detail->prix_unitaire, ','); ?> €} & {<?php echo ($detail->libelle == 'Superficie') ? formatFloat($detail->quantite, ',', 4) : formatFloat($detail->quantite, ','); ?> \texttt{<?php echo $detail->unite ?>} & <?php echo ($detail->taux_tva) ? formatFloat($detail->montant_tva, ',')." €" : null; ?> & <?php echo formatFloat($detail->montant_ht, ','); ?> € \tabularnewline
  	<?php endforeach; ?>
	\textbf{<?php echo str_replace(array("(", ")"), array('\footnotesize{(', ")}"), $ligne->libelle); ?>} \textbf{Total} & & & \textbf{<?php echo formatFloat($ligne->montant_tva, ','); ?> €} & \textbf{<?php echo formatFloat($ligne->montant_ht, ','); ?> €}  \tabularnewline
	\hline
  <?php endforeach; ?>
  \end{tabular}

\\\vspace{6mm}

\end{center}

\begin{minipage}{0.5\textwidth}
<?= escape_string_for_latex(sfConfig::get('facture_configuration_facture')['modalite_paiement']) ?>
\end{minipage}
\begin{minipage}{0.5\textwidth}
\renewcommand{\arraystretch}{1.5}
\arrayrulecolor{vertclair}
\begin{tabular}{m{2.1cm}|>{\raggedleft}m{3.8cm}|>{\raggedleft}m{2.2cm}|}
  \hhline{|~|-|-}
  & \cellcolor{verttresclair} \textbf{TOTAL HT} & \textbf{\FACTURETOTALHT~€} \tabularnewline
  \hhline{|~|-|-}
  & \cellcolor{verttresclair} \textbf{TOTAL TVA 20\%}  & \textbf{\FACTURETOTALTVA~€} \tabularnewline
  \hhline{|~|-|-}
  & \cellcolor{verttresclair} \textbf{NET À PAYER}  & \textbf{\FACTURETOTALTTC~€} \tabularnewline
  \hhline{|~|-|-}
\end{tabular}
\end{minipage}

\end{document}
