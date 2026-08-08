<?php
$diasExibidos = [];
for ($i = 0; $i < 7; $i++) $diasExibidos[] = date('Y-m-d', strtotime($inicioSemana . " +$i days"));
$detalheEvento = false;
require __DIR__ . '/_grade_horas.php';
