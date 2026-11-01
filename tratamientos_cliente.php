<?php
require_once __DIR__.'/includes/functions.php';
must_login();
require_permission('treatments_access');
check_csrf();
require_once __DIR__.'/config/database.php';
$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action']??'';
        if($action==='assign'){
            require_permission('treatments_assign');
            $client=(int)($_POST['cliente_id']??0);
            $treatment=(int)($_POST['tratamiento_id']??0);
            $start=$_POST['fecha_inicio']??'';
            if(!$client||!$treatment||!valid_date($start)) throw new RuntimeException('Completa los datos del tratamiento.');
            $q=$pdo->prepare("SELECT id FROM tratamientos WHERE id=? AND estado='Activo'");
            $q->execute([$treatment]);
            if(!$q->fetch()) throw new RuntimeException('Selecciona un tratamiento activo.');
            $q=$pdo->prepare('INSERT INTO cliente_tratamientos(cliente_id,tratamiento_id,fecha_inicio) VALUES(?,?,?)');
            $q->execute([$client,$treatment,$start]);
            flash('Tratamiento asignado al cliente.');
        }elseif($action==='session'){
            require_permission('treatments_session');
            $plan=(int)($_POST['cliente_tratamiento_id']??0);
            $specialist=(int)($_POST['especialista_id']??0);
            if($_SESSION['user']['rol']==='Especialista'){$own=(int)($_SESSION['user']['especialista_id']??0);if(!$own||$specialist!==$own)throw new RuntimeException('Solo puedes registrar sesiones con tu propio perfil.');}
            $rawDate=$_POST['fecha']??'';
            $date=DateTime::createFromFormat('Y-m-d\TH:i',$rawDate);
            if(!$plan||!$specialist||!$date||$date->format('Y-m-d\TH:i')!==$rawDate) throw new RuntimeException('Completa correctamente los datos de la sesión.');
            if($date > new DateTime()) throw new RuntimeException('No puedes registrar como realizada una sesión futura.');
            $pdo->beginTransaction();
            $q=$pdo->prepare("SELECT ct.sesiones_realizadas,t.numero_sesiones FROM cliente_tratamientos ct JOIN tratamientos t ON t.id=ct.tratamiento_id WHERE ct.id=? AND ct.estado='Activo' FOR UPDATE");
            $q->execute([$plan]);
            $record=$q->fetch();
            if(!$record) throw new RuntimeException('El tratamiento no está activo.');
            if((int)$record['sesiones_realizadas'] >= (int)$record['numero_sesiones']) throw new RuntimeException('El tratamiento ya completó sus sesiones.');
            $q=$pdo->prepare("SELECT id FROM especialistas WHERE id=? AND estado='Activo'");
            $q->execute([$specialist]);
            if(!$q->fetch()) throw new RuntimeException('Selecciona un especialista activo.');
            $q=$pdo->prepare("INSERT INTO sesiones_tratamiento(cliente_tratamiento_id,especialista_id,fecha,observaciones,estado) VALUES(?,?,?,?,'Realizada')");
            $q->execute([$plan,$specialist,$date->format('Y-m-d H:i:s'),trim($_POST['observaciones']??'')]);
            $done=(int)$record['sesiones_realizadas']+1;
            $status=$done >= (int)$record['numero_sesiones'] ? 'Finalizado' : 'Activo';
            $pdo->prepare("UPDATE cliente_tratamientos SET sesiones_realizadas=?,estado=?,fecha_fin=CASE WHEN ?='Finalizado' THEN CURDATE() ELSE fecha_fin END WHERE id=?")->execute([$done,$status,$status,$plan]);
            $pdo->commit();
            flash('Sesión registrada. Progreso: '.$done.'/'.$record['numero_sesiones']);
        }else{
            throw new RuntimeException('Acción no válida.');
        }
        go('tratamientos_cliente.php');
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        $error=$e instanceof PDOException?'No se pudo completar la operación. Verifica los datos.':$e->getMessage();
    }
}
$role=$_SESSION['user']['rol'];
$clients=in_array($role,['Administrador','Recepcionista'],true)?$pdo->query('SELECT id,nombres,apellidos FROM clientes ORDER BY nombres')->fetchAll():[];
$treatments=in_array($role,['Administrador','Recepcionista'],true)?$pdo->query("SELECT id,nombre,numero_sesiones FROM tratamientos WHERE estado='Activo' ORDER BY nombre")->fetchAll():[];
$specs=[];if($role==='Administrador')$specs=$pdo->query("SELECT id,nombres,apellidos FROM especialistas WHERE estado='Activo' ORDER BY nombres")->fetchAll();elseif($role==='Especialista'){$q=$pdo->prepare("SELECT id,nombres,apellidos FROM especialistas WHERE id=? AND estado='Activo'");$q->execute([(int)$_SESSION['user']['especialista_id']]);$specs=$q->fetchAll();}
$plans=$pdo->query('SELECT ct.id,ct.fecha_inicio,ct.fecha_fin,ct.sesiones_realizadas,ct.estado,CONCAT(c.nombres," ",c.apellidos) cliente,t.nombre tratamiento,t.numero_sesiones FROM cliente_tratamientos ct JOIN clientes c ON c.id=ct.cliente_id JOIN tratamientos t ON t.id=ct.tratamiento_id ORDER BY ct.id DESC')->fetchAll();
$sessions=$pdo->query('SELECT st.fecha,st.estado,st.observaciones,CONCAT(c.nombres," ",c.apellidos) cliente,t.nombre tratamiento,CONCAT(e.nombres," ",e.apellidos) especialista FROM sesiones_tratamiento st JOIN cliente_tratamientos ct ON ct.id=st.cliente_tratamiento_id JOIN clientes c ON c.id=ct.cliente_id JOIN tratamientos t ON t.id=ct.tratamiento_id JOIN especialistas e ON e.id=st.especialista_id ORDER BY st.fecha DESC LIMIT 100')->fetchAll();
$title='Tratamientos de clientes';
require __DIR__.'/includes/header.php';
?>
<div class="heading"><div><span class="eyebrow">PLANES Y PROCEDIMIENTOS</span><h1>Tratamientos de clientes</h1><p class="muted">Asigna planes y controla cada sesión realizada.</p></div></div>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<div class="split">
<section class="panel form-panel">
<?php if(in_array($role,['Administrador','Recepcionista'],true)):?>
<h2>Asignar tratamiento</h2>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="assign">
<label class="field"><span>Cliente</span><select name="cliente_id" required><option value="">Seleccionar cliente</option><?php foreach($clients as $c):?><option value="<?=$c['id']?>"><?=e($c['nombres'].' '.$c['apellidos'])?></option><?php endforeach;?></select></label>
<label class="field"><span>Tratamiento</span><select name="tratamiento_id" required><option value="">Seleccionar tratamiento</option><?php foreach($treatments as $t):?><option value="<?=$t['id']?>"><?=e($t['nombre'].' · '.$t['numero_sesiones'].' sesiones')?></option><?php endforeach;?></select></label>
<label class="field"><span>Fecha de inicio</span><input type="date" name="fecha_inicio" value="<?=date('Y-m-d')?>" required></label><button class="btn">Asignar tratamiento</button></form>
<?php endif;?>
<?php if(in_array($role,['Administrador','Especialista'],true)):?>
<?php if(in_array($role,['Administrador','Recepcionista'],true)):?><hr><?php endif;?>
<h2>Registrar sesión realizada</h2>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="session">
<label class="field"><span>Tratamiento activo</span><select name="cliente_tratamiento_id" required><option value="">Seleccionar plan</option><?php foreach($plans as $a):if($a['estado']==='Activo'):?><option value="<?=$a['id']?>"><?=e($a['cliente'].' · '.$a['tratamiento'].' ('.$a['sesiones_realizadas'].'/'.$a['numero_sesiones'].')')?></option><?php endif;endforeach;?></select></label>
<label class="field"><span>Especialista</span><select name="especialista_id" required><option value="">Seleccionar especialista</option><?php foreach($specs as $sp):?><option value="<?=$sp['id']?>"><?=e($sp['nombres'].' '.$sp['apellidos'])?></option><?php endforeach;?></select></label>
<label class="field"><span>Fecha y hora</span><input type="datetime-local" name="fecha" value="<?=date('Y-m-d\TH:i')?>" required></label>
<label class="field"><span>Observaciones</span><textarea name="observaciones" rows="2"></textarea></label><button class="btn">Registrar sesión</button></form>
<?php endif;?>
</section>
<section class="panel table-panel"><h2>Planes de tratamiento</h2><div class="tablewrap"><table><thead><tr><th>Cliente</th><th>Tratamiento</th><th>Sesiones</th><th>Estado</th></tr></thead><tbody><?php foreach($plans as $a):?><tr><td><?=e($a['cliente'])?></td><td><?=e($a['tratamiento'])?></td><td><?=e($a['sesiones_realizadas'].' / '.$a['numero_sesiones'])?></td><td><span class="badge"><?=e($a['estado'])?></span></td></tr><?php endforeach;?><?php if(!$plans):?><tr><td colspan="4" class="empty">No hay tratamientos asignados.</td></tr><?php endif;?></tbody></table></div></section>
</div>
<section class="panel table-panel"><h2>Historial de sesiones</h2><div class="tablewrap"><table><thead><tr><th>Fecha</th><th>Cliente</th><th>Tratamiento</th><th>Especialista</th><th>Estado</th><th>Observaciones</th></tr></thead><tbody><?php foreach($sessions as $ss):?><tr><td><?=e($ss['fecha'])?></td><td><?=e($ss['cliente'])?></td><td><?=e($ss['tratamiento'])?></td><td><?=e($ss['especialista'])?></td><td><?=e($ss['estado'])?></td><td><?=e($ss['observaciones'])?></td></tr><?php endforeach;?><?php if(!$sessions):?><tr><td colspan="6" class="empty">No hay sesiones registradas.</td></tr><?php endif;?></tbody></table></div></section>
<?php require __DIR__.'/includes/footer.php';?>
