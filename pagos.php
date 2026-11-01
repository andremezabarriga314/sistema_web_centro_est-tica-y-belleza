<?php
require_once __DIR__.'/includes/functions.php';must_login();require_permission('payments');check_csrf();require_once __DIR__.'/config/database.php';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $cid=($_POST['cita_id']??'')!==''?(int)$_POST['cita_id']:null;
  $ctid=($_POST['cliente_tratamiento_id']??'')!==''?(int)$_POST['cliente_tratamiento_id']:null;
  if(!$cid&&!$ctid)throw new RuntimeException('Asocia el pago a una cita o tratamiento.');
  if($cid&&$ctid)throw new RuntimeException('Selecciona solo un concepto.');
  $amount=round((float)($_POST['monto']??0),2);
  $method=$_POST['metodo']??'';$status=$_POST['estado']??'';
  if(!in_array($method,['Efectivo','Tarjeta de crédito','Tarjeta de débito','Yape'],true)||!in_array($status,['Pagado','Pendiente','Anulado'],true))throw new RuntimeException('Método o estado de pago no válido.');
  if($amount<=0)throw new RuntimeException('El monto debe ser mayor que cero.');
  $pdo->beginTransaction();
  if($cid){
   $q=$pdo->prepare('SELECT s.precio FROM citas c JOIN servicios s ON s.id=c.servicio_id WHERE c.id=? FOR UPDATE');
   $q->execute([$cid]);$total=$q->fetchColumn();
   if($total===false)throw new RuntimeException('La cita seleccionada no existe.');
   $q=$pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE cita_id=? AND estado='Pagado'");$q->execute([$cid]);$paid=(float)$q->fetchColumn();
  }else{
   $q=$pdo->prepare('SELECT t.precio FROM cliente_tratamientos ct JOIN tratamientos t ON t.id=ct.tratamiento_id WHERE ct.id=? FOR UPDATE');
   $q->execute([$ctid]);$total=$q->fetchColumn();
   if($total===false)throw new RuntimeException('El tratamiento seleccionado no existe.');
   $q=$pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE cliente_tratamiento_id=? AND estado='Pagado'");$q->execute([$ctid]);$paid=(float)$q->fetchColumn();
  }
  if($status==='Pagado' && $paid+$amount>(float)$total+0.009)throw new RuntimeException('El pago supera el saldo pendiente del concepto. Saldo disponible: '.money(max(0,(float)$total-$paid)));
  $q=$pdo->prepare('INSERT INTO pagos(cita_id,cliente_tratamiento_id,monto,metodo,estado,referencia) VALUES(?,?,?,?,?,?)');
  $q->execute([$cid,$ctid,$amount,$method,$status,trim($_POST['referencia']??'')]);
  $pdo->commit();flash('Pago registrado correctamente.');go('pagos.php');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof PDOException?'No se pudo registrar el pago. Revisa la información.':$e->getMessage();}
}
$citas=$pdo->query('SELECT c.id,c.fecha,CONCAT(cl.nombres," ",cl.apellidos) cliente,s.nombre servicio,s.precio FROM citas c JOIN clientes cl ON cl.id=c.cliente_id JOIN servicios s ON s.id=c.servicio_id ORDER BY c.id DESC LIMIT 200')->fetchAll();$cts=$pdo->query('SELECT ct.id,CONCAT(c.nombres," ",c.apellidos) cliente,t.nombre tratamiento,t.precio FROM cliente_tratamientos ct JOIN clientes c ON c.id=ct.cliente_id JOIN tratamientos t ON t.id=ct.tratamiento_id ORDER BY ct.id DESC')->fetchAll();$rows=$pdo->query('SELECT p.*,CONCAT(cl.nombres," ",cl.apellidos) cliente,COALESCE(s.nombre,t.nombre,"—") concepto FROM pagos p LEFT JOIN citas c ON c.id=p.cita_id LEFT JOIN clientes cl ON cl.id=COALESCE(c.cliente_id,(SELECT ct.cliente_id FROM cliente_tratamientos ct WHERE ct.id=p.cliente_tratamiento_id)) LEFT JOIN servicios s ON s.id=c.servicio_id LEFT JOIN cliente_tratamientos ct ON ct.id=p.cliente_tratamiento_id LEFT JOIN tratamientos t ON t.id=ct.tratamiento_id ORDER BY p.id DESC LIMIT 150')->fetchAll();$title='Pagos';require __DIR__.'/includes/header.php';?>
<div class="heading"><div><span class="eyebrow">CAJA Y COBRANZAS</span><h1>Pagos</h1><p class="muted">Registra pagos de citas y tratamientos.</p></div></div><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?><div class="split"><section class="panel form-panel"><h2>Registrar pago</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label class="field"><span>Pago de cita</span><select name="cita_id"><option value="">No aplica</option><?php foreach($citas as $c):?><option value="<?=$c['id']?>"><?=e('#'.$c['id'].' · '.$c['cliente'].' · '.$c['servicio'].' · '.money($c['precio']))?></option><?php endforeach;?></select></label><label class="field"><span>Pago de tratamiento</span><select name="cliente_tratamiento_id"><option value="">No aplica</option><?php foreach($cts as $c):?><option value="<?=$c['id']?>"><?=e('#'.$c['id'].' · '.$c['cliente'].' · '.$c['tratamiento'].' · '.money($c['precio']))?></option><?php endforeach;?></select></label><label class="field"><span>Monto (S/)</span><input type="number" step="0.01" min="0.01" name="monto" required></label><label class="field"><span>Método de pago</span><select name="metodo"><option>Efectivo</option><option>Tarjeta de crédito</option><option>Tarjeta de débito</option><option>Yape</option></select></label><label class="field"><span>Estado</span><select name="estado"><option>Pagado</option><option>Pendiente</option><option>Anulado</option></select></label><label class="field"><span>Referencia / operación</span><input name="referencia"></label><button class="btn">Guardar pago</button></form></section><section class="panel table-panel"><h2>Historial de pagos</h2><div class="tablewrap"><table><tr><th>Fecha</th><th>Cliente</th><th>Concepto</th><th>Monto</th><th>Método</th><th>Estado</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['fecha_pago'])?></td><td><?=e($r['cliente']??'—')?></td><td><?=e($r['concepto'])?></td><td><?=money($r['monto'])?></td><td><?=e($r['metodo'])?></td><td><?=e($r['estado'])?></td></tr><?php endforeach;?></table></div></section></div><?php require __DIR__.'/includes/footer.php';?>
