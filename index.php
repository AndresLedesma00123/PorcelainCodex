<?php
$pageTitle = 'Porcelain Codex';
$currentMode = 'cartografo';
$viewMode = 'edit';
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
<div class="mode-switch" aria-label="Modo principal">
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

<div class="nearby-scroll">
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

<article class="nearby-item">
<h3>Astillero Roto</h3>
<p>Barcos quebrados y contrabandistas.</p>
</article>

<article class="nearby-item">
<h3>Faro de Marfil</h3>
<p>Luz arcana que nunca se apaga.</p>
</article>

<article class="nearby-item">
<h3>Cruce del Peregrino</h3>
<p>Campamento usado por viajeros.</p>
</article>

<article class="nearby-item">
<h3>Templo de Sal</h3>
<p>Santuario viejo, erosionado y blanco.</p>
</article>

<article class="nearby-item">
<h3>Valle de los Ecos</h3>
<p>Voces repetidas entre rocas húmedas.</p>
</article>

<article class="nearby-item">
<h3>Sueños de porcelana</h3>
<p>El barco ha cambiado, pero sigue siendo tu hogar.</p>
</article>

<article class="nearby-item">
<h3>Corazones de terciopelo</h3>
<p>Sientes una extraña sensación de calidez y hogar.</p>
</article>


</div>
</div>
</div>
</section>

<section class="content-grid">
<div class="main-panel">
<form class="entry-form" action="#" method="post" enctype="multipart/form-data">
<div class="panel-toolbar">
<div class="panel-actions-left">
<button type="button" class="toolbar-button">Agregar nuevo</button>
</div>

<div class="panel-actions-right">
<button type="button" class="toolbar-button">Ver</button>
<button type="button" class="toolbar-button">Editar</button>
<button type="submit" class="toolbar-button toolbar-button--primary">Guardar</button>
</div>
</div>

<div class="entry-form-layout">
<div class="entry-form-main">
<section class="image-upload-card">
<label for="entry-image" class="image-upload-label">
<span class="image-upload-plus">+</span>
<span class="image-upload-text">Haz clic aquí para subir una imagen</span>
</label>
<input id="entry-image" name="entry_image" type="file" accept="image/*" class="visually-hidden">
</section>

<section class="form-card">
<div class="form-field">
<label for="entry-name">Nombre</label>
<input
type="text"
id="entry-name"
name="entry_name"
value="Ciudad Pantano"
placeholder="Escribe el nombre del lugar"
>
</div>

<div class="form-field">
<label for="entry-type">Tipo</label>
<select id="entry-type" name="entry_type">
<option value="ciudad" selected>Ciudad</option>
<option value="mazmorra">Mazmorra</option>
<option value="ruina">Ruina</option>
<option value="bosque">Bosque</option>
</select>
</div>

<div class="form-field">
<label for="entry-description">Descripción general</label>
<textarea
id="entry-description"
name="entry_description"
rows="5"
placeholder="Describe brevemente el lugar o criatura"
>Antigua ciudad comercial caída en desgracia tras las guerras del litoral.</textarea>
</div>
</section>

<section class="attributes-section">
<article class="attribute-card">
<div class="attribute-card__header">
<label for="attr-name-1">Nombre del tipo de dato</label>
<button type="button" class="attribute-remove-button" aria-label="Eliminar este tipo de dato">×</button>
</div>
<input
type="text"
id="attr-name-1"
name="attributes[0][name]"
value="Población"
placeholder="Ejemplo: Población"
>
<label for="attr-value-1" class="attribute-value-label">Contenido</label>
<textarea
id="attr-value-1"
name="attributes[0][value]"
rows="3"
placeholder="Escribe el contenido de este dato"
>Escasa, dispersa y desconfiada.</textarea>
</article>

<article class="attribute-card">
<div class="attribute-card__header">
<label for="attr-name-2">Nombre del tipo de dato</label>
<button type="button" class="attribute-remove-button" aria-label="Eliminar este tipo de dato">×</button>
</div>
<input
type="text"
id="attr-name-2"
name="attributes[1][name]"
value="Puntos de interés"
placeholder="Ejemplo: Puntos de interés"
>
<label for="attr-value-2" class="attribute-value-label">Contenido</label>
<textarea
id="attr-value-2"
name="attributes[1][value]"
rows="3"
placeholder="Escribe el contenido de este dato"
>Muelles rotos, mercado cubierto y criptas bajo el agua.</textarea>
</article>

<button type="button" class="add-attribute-button">+ Agregar tipo de dato</button>
</section>
</div>

<aside class="entry-form-sidebar">
<section class="sidebar-card sidebar-list-card">
<h2 class="section-title">Lista de lugares</h2>

<div class="search-box">
<label for="place-search" class="visually-hidden">Buscar lugar</label>
<input
type="search"
id="place-search"
class="search-input"
placeholder="Buscar lugar..."
>
</div>

<ul class="scrollable-list">
<li>Astillero Roto</li>
<li>Ciudad Pantano</li>
<li>Cruce del Peregrino</li>
<li>Faro de Marfil</li>
<li>Gruta de Nacre</li>
<li>Templo de Sal</li>
<li>Torre Hundida</li>
<li>Valle de los Ecos</li>
<li>Zanja del Wyrm</li>
<li>Barco de porcelana</li>
</ul>
</section>

<section class="sidebar-card">
<h2 class="section-title">Notas</h2>

<div class="form-field">
<label for="entry-notes">Notas rápidas</label>
<textarea
id="entry-notes"
name="entry_notes"
rows="10"
placeholder="Escribe notas adicionales aquí"
>Ciudad usada como paso por contrabandistas. Revisar si debe conectarse con la mazmorra del litoral.</textarea>
</div>
</section>
</aside>
</div>
</form>
</div>
</section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
