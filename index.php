<?php
$pageTitle = 'Porcelain Codex';
$currentMode = 'cartografo';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="page">
<section class="map-section">
<div class="mode-switch">
<button class="mode-button mode-button--active" type="button">Cartógrafo</button>
<button class="mode-button" type="button">Bestiario</button>
</div>

<div class="map-card">
<div class="map-placeholder">
<span>Mapa de la región</span>
</div>
</div>

<div class="nearby-card">
<h2 class="section-title">Lugares cercanos</h2>

<div class="nearby-list">
<article class="nearby-item">
<h3>Ciudad Pantano</h3>
<p>Ruinas antiguas y canales oscuros.</p>
</article>

<article class="nearby-item">
<h3>Gruta de Nacre</h3>
<p>Entrada estrecha, humedad y ecos.</p>
</article>

<article class="nearby-item">
<h3>Torre Hundida</h3>
<p>Restos de vigilancia costera.</p>
</article>
</div>
</div>
</section>

<section class="content-grid">
<div class="main-panel">
<div class="panel-toolbar">
<div class="panel-actions-left">
<button type="button" class="toolbar-button">Agregar nuevo</button>
</div>

<div class="panel-actions-right">
<button type="button" class="toolbar-button">Ver</button>
<button type="button" class="toolbar-button">Editar</button>
<button type="button" class="toolbar-button toolbar-button--primary">Guardar</button>
</div>
</div>

<div class="entry-view">
<div class="entry-image-placeholder">
<span>Imagen del lugar o criatura</span>
</div>

<div class="entry-details">
<div class="detail-block">
<h3>Nombre</h3>
<p>Ciudad Pantano</p>
</div>

<div class="detail-block">
<h3>Historia</h3>
<p>
Antigua ciudad comercial caída en desgracia tras las guerras del litoral.
</p>
</div>

<div class="detail-block">
<h3>Población</h3>
<p>Escasa, dispersa y desconfiada.</p>
</div>
</div>
</div>
</div>

<aside class="sidebar">
<section class="sidebar-card">
<h2 class="section-title">Lista de lugares</h2>
<ul class="simple-list">
<li>Ciudad Pantano</li>
<li>Gruta de Nacre</li>
<li>Torre Hundida</li>
<li>Faro de Marfil</li>
</ul>
</section>

<section class="sidebar-card">
<h2 class="section-title">Notas</h2>
<p class="notes-text">
Aquí aparecerán notas breves ligadas al lugar o criatura seleccionada.
</p>
</section>
</aside>
</section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
