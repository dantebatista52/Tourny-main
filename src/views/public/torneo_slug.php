<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($torneo['nombre'] ?? 'Torneo') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f8f9fa; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; }
        .jornada-card { border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .jornada-title { background-color: #f4f4f4; padding: 5px 10px; margin-top: 0; border-radius: 4px; }
        .partido-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; }
        .partido-row:last-child { border-bottom: none; }
        .equipo { flex: 1; text-align: center; font-weight: bold; }
        .marcador { background: #e9ecef; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-left { text-align: left; }
    </style>
</head>
<body>

<div class="container">
    <h1>🏆 <?= htmlspecialchars($torneo['nombre']) ?></h1>
    <p>Formato: <?= ucfirst(htmlspecialchars($torneo['formato'])) ?></p>

    <!-- TABLA DE POSICIONES -->
    <h2>📊 Tabla de Posiciones</h2>
    <?php if (empty($tabla)): ?>
        <p>No hay datos disponibles.</p>
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
                        <td><?= $pos++ ?></td>
                        <td class="text-left"><strong><?= htmlspecialchars($fila['nombre']) ?></strong></td>
                        <td><strong><?= $fila['pts'] ?></strong></td>
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

    <!-- FIXTURE Y RESULTADOS -->
    <h2>📅 Fixture de Partidos</h2>
    <?php
    $jornadas = [];
    foreach ($partidos as $partido) {
        $numFecha = $partido['fecha_numero'] ?? 1;
        $jornadas[$numFecha][] = $partido;
    }
    ?>

    <?php foreach ($jornadas as $numJornada => $listaPartidos): ?>
        <div class="jornada-card">
            <h3 class="jornada-title">Fecha <?= $numJornada ?></h3>
            <?php foreach ($listaPartidos as $p): ?>
                <div class="partido-row">
                    <?php if (empty($p['visitante_nombre'])): ?>
                        <span class="equipo" style="color: #6c757d; font-style: italic;">
                            🟢 <?= htmlspecialchars($p['local_nombre']) ?> (Fecha Libre)
                        </span>
                    <?php elseif (empty($p['local_nombre'])): ?>
                        <span class="equipo" style="color: #6c757d; font-style: italic;">
                            🟢 <?= htmlspecialchars($p['visitante_nombre']) ?> (Fecha Libre)
                        </span>
                    <?php else: ?>
                        <span class="equipo"><?= htmlspecialchars($p['local_nombre']) ?></span>
                        <span class="marcador">
                            <?= $p['goles_local'] ?? '-' ?> : <?= $p['goles_visitante'] ?? '-' ?>
                        </span>
                        <span class="equipo"><?= htmlspecialchars($p['visitante_nombre']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>