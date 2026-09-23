<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Posiciones - <?= htmlspecialchars($torneo['nombre'] ?? 'Torneo') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .header { margin-bottom: 20px; }
        .btn { padding: 6px 12px; cursor: pointer; text-decoration: none; background: #007bff; color: white; border-radius: 4px; display: inline-block; }
        .btn-secondary { background-color: #6c757d; }
        
        table { width: 100%; max-width: 800px; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-left { text-align: left; }
        .posicion { font-weight: bold; width: 40px; }
        .puntos { background-color: #e9ecef; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Tabla de Posiciones: <?= htmlspecialchars($torneo['nombre'] ?? '') ?></h1>
        <a href="/torneos/<?= $torneo['id'] ?>/fixture" class="btn btn-secondary">← Volver al Fixture</a>
        <a href="/dashboard" class="btn btn-secondary">Dashboard</a>
    </div>

    <hr>

    <?php if (empty($tabla)): ?>
        <p>No hay equipos registrados en este torneo.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th class="text-left">Equipo</th>
                    <th>PTS</th>
                    <th>PJ</th>
                    <th>PG</th>
                    <th>PE</th>
                    <th>PP</th>
                    <th>GF</th>
                    <th>GC</th>
                    <th>DG</th>
                </tr>
            </thead>
            <tbody>
                <?php $pos = 1; ?>
                <?php foreach ($tabla as $fila): ?>
                    <tr>
                        <td class="posicion"><?= $pos++ ?></td>
                        <td class="text-left"><strong><?= htmlspecialchars($fila['nombre']) ?></strong></td>
                        <td class="puntos"><?= $fila['pts'] ?></td>
                        <td><?= $fila['pj'] ?></td>
                        <td><?= $fila['pg'] ?></td>
                        <td><?= $fila['pe'] ?></td>
                        <td><?= $fila['pp'] ?></td>
                        <td><?= $fila['gf'] ?></td>
                        <td><?= $fila['gc'] ?></td>
                        <td><?= ($fila['dg'] > 0 ? '+'.$fila['dg'] : $fila['dg']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>