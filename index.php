<?php
$pageTitle = 'Porcelain Codex';
$currentMode = 'cartografo';
$viewMode = 'edit';

$allowedTypes = ['ciudad', 'mazmorra', 'ruina', 'bosque'];
$allowedImageMimeTypes = [
    'image/jpeg' => 'jpg',
'image/png' => 'png',
'image/webp' => 'webp',
];
$maxImageSize = 2 * 1024 * 1024;

$errors = [];
$successMessage = '';

$projectRoot = __DIR__;
$dataDirectory = $projectRoot . '/data';
$uploadsDirectory = $projectRoot . '/assets/img/uploads';
$jsonFilePath = $dataDirectory . '/entries.json';

$formData = [
    'entry_name' => 'Ciudad Pantano',
'entry_type' => 'ciudad',
'entry_description' => 'Antigua ciudad comercial caída en desgracia tras las guerras del litoral.',
'entry_notes' => 'Ciudad usada como paso por contrabandistas. Revisar si debe conectarse con la mazmorra del litoral.',
'image_path' => '',
'attributes' => [
    [
        'name' => 'Población',
'value' => 'Escasa, dispersa y desconfiada.',
    ],
[
    'name' => 'Puntos de interés',
'value' => 'Muelles rotos, mercado cubierto y criptas bajo el agua.',
],
],
];

function clean_input(string $value): string
{
    return trim($value);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $value): string
{
    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value);
    $value = trim($value, '-');

    if ($value === '') {
        return 'entrada';
    }

    return $value;
}

function ensure_directory(string $path): bool
{
    if (is_dir($path)) {
        return true;
    }

    return mkdir($path, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['entry_name'] = clean_input($_POST['entry_name'] ?? '');
    $formData['entry_type'] = clean_input($_POST['entry_type'] ?? '');
    $formData['entry_description'] = clean_input($_POST['entry_description'] ?? '');
    $formData['entry_notes'] = clean_input($_POST['entry_notes'] ?? '');

    $submittedAttributes = $_POST['attributes'] ?? [];
    $formData['attributes'] = [];

    if ($formData['entry_name'] === '') {
        $errors['entry_name'] = 'Debes escribir un nombre.';
    } elseif (mb_strlen($formData['entry_name']) < 3) {
        $errors['entry_name'] = 'El nombre debe tener al menos 3 caracteres.';
    }

    if (!in_array($formData['entry_type'], $allowedTypes, true)) {
        $errors['entry_type'] = 'Debes seleccionar un tipo válido.';
    }

    if ($formData['entry_description'] === '') {
        $errors['entry_description'] = 'Debes escribir una descripción general.';
    } elseif (mb_strlen($formData['entry_description']) < 10) {
        $errors['entry_description'] = 'La descripción debe tener al menos 10 caracteres.';
    }

    if ($formData['entry_notes'] !== '' && mb_strlen($formData['entry_notes']) < 5) {
        $errors['entry_notes'] = 'Si escribes notas, deben tener al menos 5 caracteres.';
    }

    if (is_array($submittedAttributes)) {
        foreach ($submittedAttributes as $index => $attribute) {
            $attributeName = clean_input($attribute['name'] ?? '');
            $attributeValue = clean_input($attribute['value'] ?? '');

            $formData['attributes'][] = [
                'name' => $attributeName,
                'value' => $attributeValue,
            ];

            if ($attributeName === '' && $attributeValue === '') {
                continue;
            }

            if ($attributeName === '') {
                $errors["attributes_$index" . '_name'] = 'Este tipo de dato necesita un nombre.';
            }

            if ($attributeValue === '') {
                $errors["attributes_$index" . '_value'] = 'Este tipo de dato necesita contenido.';
            }
        }
    }

    if (empty($formData['attributes'])) {
        $formData['attributes'][] = ['name' => '', 'value' => ''];
    }

    $uploadedImage = $_FILES['entry_image'] ?? null;
    $pendingImageData = null;

    if ($uploadedImage !== null && $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($uploadedImage['error'] !== UPLOAD_ERR_OK) {
            $errors['entry_image'] = 'Hubo un problema al subir la imagen.';
        } elseif ($uploadedImage['size'] > $maxImageSize) {
            $errors['entry_image'] = 'La imagen no puede pesar más de 2 MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo === false) {
                $errors['entry_image'] = 'No se pudo validar el tipo real de la imagen.';
            } else {
                $detectedMimeType = finfo_file($finfo, $uploadedImage['tmp_name']);
                finfo_close($finfo);

                if ($detectedMimeType === false || !isset($allowedImageMimeTypes[$detectedMimeType])) {
                    $errors['entry_image'] = 'Solo se permiten imágenes JPG, PNG o WEBP.';
                } else {
                    $pendingImageData = [
                        'tmp_name' => $uploadedImage['tmp_name'],
                        'extension' => $allowedImageMimeTypes[$detectedMimeType],
                    ];
                }
            }
        }
    }

    if (empty($errors)) {
        if (!ensure_directory($dataDirectory)) {
            $errors['storage'] = 'No se pudo crear la carpeta de datos.';
        }

        if (!ensure_directory($uploadsDirectory)) {
            $errors['storage'] = 'No se pudo crear la carpeta de imágenes.';
        }
    }

    if (empty($errors)) {
        $savedImagePath = '';

        if ($pendingImageData !== null) {
            $safeFileName = slugify($formData['entry_name']) . '-' . time() . '.' . $pendingImageData['extension'];
            $destinationPath = $uploadsDirectory . '/' . $safeFileName;

            if (!move_uploaded_file($pendingImageData['tmp_name'], $destinationPath)) {
                $errors['entry_image'] = 'No se pudo mover la imagen subida.';
            } else {
                $savedImagePath = 'assets/img/uploads/' . $safeFileName;
                $formData['image_path'] = $savedImagePath;
            }
        }
    }

    if (empty($errors)) {
        $existingEntries = [];

        if (file_exists($jsonFilePath)) {
            $jsonContent = file_get_contents($jsonFilePath);

            if ($jsonContent === false) {
                $errors['storage'] = 'No se pudo leer el archivo de datos.';
            } else {
                $decodedEntries = json_decode($jsonContent, true);

                if (is_array($decodedEntries)) {
                    $existingEntries = $decodedEntries;
                }
            }
        }
    }

    if (empty($errors)) {
        $cleanAttributes = [];

        foreach ($formData['attributes'] as $attribute) {
            if ($attribute['name'] === '' && $attribute['value'] === '') {
                continue;
            }

            $cleanAttributes[] = [
                'name' => $attribute['name'],
                'value' => $attribute['value'],
            ];
        }

        $newEntry = [
            'id' => uniqid('entry_', true),
            'name' => $formData['entry_name'],
            'type' => $formData['entry_type'],
            'description' => $formData['entry_description'],
            'notes' => $formData['entry_notes'],
            'image_path' => $formData['image_path'],
            'attributes' => $cleanAttributes,
            'created_at' => date('c'),
        ];

        $existingEntries[] = $newEntry;

        $encodedJson = json_encode(
            $existingEntries,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($encodedJson === false) {
            $errors['storage'] = 'No se pudieron convertir los datos a JSON.';
        } else {
            $writeResult = file_put_contents($jsonFilePath, $encodedJson);

            if ($writeResult === false) {
                $errors['storage'] = 'No se pudo guardar el archivo JSON.';
            } else {
                $successMessage = 'Entrada guardada correctamente en el archivo JSON.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
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
<form class="entry-form" action="" method="post" enctype="multipart/form-data" novalidate>
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

<?php if (!empty($errors)): ?>
<div class="form-message form-message--error">
<p><strong>Hay errores en el formulario.</strong> Revisa los campos marcados abajo.</p>
</div>
<?php endif; ?>

<?php if (isset($errors['storage'])): ?>
<div class="form-message form-message--error">
<p><?= e($errors['storage']) ?></p>
</div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
<div class="form-message form-message--success">
<p><?= e($successMessage) ?></p>
</div>
<?php endif; ?>

<div class="entry-form-layout">
<div class="entry-form-main">
<section class="image-upload-card">
<label for="entry-image" class="image-upload-label">
<span class="image-upload-plus">+</span>
<span class="image-upload-text">Haz clic aquí para subir una imagen</span>
</label>
<input id="entry-image" name="entry_image" type="file" accept="image/*" class="visually-hidden">
<?php if (isset($errors['entry_image'])): ?>
<p class="field-error"><?= e($errors['entry_image']) ?></p>
<?php endif; ?>

<?php if ($formData['image_path'] !== ''): ?>
<p class="upload-hint">Imagen preparada o guardada: <?= e($formData['image_path']) ?></p>
<?php endif; ?>
</section>

<section class="form-card">
<div class="form-field">
<label for="entry-name">Nombre</label>
<input
type="text"
id="entry-name"
name="entry_name"
value="<?= e($formData['entry_name']) ?>"
placeholder="Escribe el nombre del lugar"
>
<?php if (isset($errors['entry_name'])): ?>
<p class="field-error"><?= e($errors['entry_name']) ?></p>
<?php endif; ?>
</div>

<div class="form-field">
<label for="entry-type">Tipo</label>
<select id="entry-type" name="entry_type">
<option value="ciudad" <?= $formData['entry_type'] === 'ciudad' ? 'selected' : '' ?>>Ciudad</option>
<option value="mazmorra" <?= $formData['entry_type'] === 'mazmorra' ? 'selected' : '' ?>>Mazmorra</option>
<option value="ruina" <?= $formData['entry_type'] === 'ruina' ? 'selected' : '' ?>>Ruina</option>
<option value="bosque" <?= $formData['entry_type'] === 'bosque' ? 'selected' : '' ?>>Bosque</option>
</select>
<?php if (isset($errors['entry_type'])): ?>
<p class="field-error"><?= e($errors['entry_type']) ?></p>
<?php endif; ?>
</div>

<div class="form-field">
<label for="entry-description">Descripción general</label>
<textarea
id="entry-description"
name="entry_description"
rows="5"
placeholder="Describe brevemente el lugar o criatura"
><?= e($formData['entry_description']) ?></textarea>
<?php if (isset($errors['entry_description'])): ?>
<p class="field-error"><?= e($errors['entry_description']) ?></p>
<?php endif; ?>
</div>
</section>

<section class="attributes-section">
<?php foreach ($formData['attributes'] as $index => $attribute): ?>
<article class="attribute-card">
<div class="attribute-card__header">
<label for="attr-name-<?= $index ?>">Tipo de dato</label>
<button type="button" class="attribute-remove-button" aria-label="Eliminar este tipo de dato">×</button>
</div>

<input
type="text"
id="attr-name-<?= $index ?>"
name="attributes[<?= $index ?>][name]"
value="<?= e($attribute['name']) ?>"
placeholder="Ejemplo: Población"
>

<?php if (isset($errors["attributes_$index" . '_name'])): ?>
<p class="field-error"><?= e($errors["attributes_$index" . '_name']) ?></p>
<?php endif; ?>

<label for="attr-value-<?= $index ?>" class="attribute-value-label">Contenido</label>
<textarea
id="attr-value-<?= $index ?>"
name="attributes[<?= $index ?>][value]"
rows="3"
placeholder="Escribe el contenido de este dato"
><?= e($attribute['value']) ?></textarea>

<?php if (isset($errors["attributes_$index" . '_value'])): ?>
<p class="field-error"><?= e($errors["attributes_$index" . '_value']) ?></p>
<?php endif; ?>
</article>
<?php endforeach; ?>

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
><?= e($formData['entry_notes']) ?></textarea>
<?php if (isset($errors['entry_notes'])): ?>
<p class="field-error"><?= e($errors['entry_notes']) ?></p>
<?php endif; ?>
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
