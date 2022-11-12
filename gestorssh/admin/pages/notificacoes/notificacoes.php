<?php
	if (basename($_SERVER["REQUEST_URI"]) === basename(__FILE__))
{
	exit('<h1>ERROR 404</h1>Entre em contato conosco e envie detalhes.');
}

if(isset($_GET['ler'])){

switch($_GET['ler']){
case 'all':$ler='tudo';$tipo='tudo';break;
case 'others':$ler='others';$tipo='outros';break;
case 'fatu':$ler='fatu';$tipo='fatura';break;
case 'accs':$ler='contas';$tipo='conta';break;
case 'reve':$ler='revenda';$tipo='revenda';break;
case 'chamados':$ler='chamados';$tipo='chamados';break;
default:$ler='nada';break;
}
if($ler=='nada'){

}elseif($ler=='tudo'){

// Notificações
        $SQLnoti= "select * from  notificacoes where lido='nao' and admin='sim'";
        $SQLnoti = $conn->prepare($SQLnoti);
        $SQLnoti->execute();
        $totalnoti = $SQLnoti->rowCount();

        if($totalnoti>0){
        while($tudo= $SQLnoti->fetch()){

        $SQLnotiler= "update notificacoes set lido='sim' where id='".$tudo['id']."'";
        $SQLnotiler = $conn->prepare($SQLnotiler);
        $SQLnotiler->execute();

        }
        }

}elseif($ler=='others'){

        // Notificações
        $SQLnoti= "select * from  notificacoes where lido='nao' and tipo='outros' and admin='sim'";
        $SQLnoti = $conn->prepare($SQLnoti);
        $SQLnoti->execute();
        $totalnoti = $SQLnoti->rowCount();

        if($totalnoti>0){
        while($tudo= $SQLnoti->fetch()){

        $SQLnotiler= "update notificacoes set lido='sim' where id='".$tudo['id']."'";
        $SQLnotiler = $conn->prepare($SQLnotiler);
        $SQLnotiler->execute();

        }
        }

}elseif($ler=='fatu'){

        // Notificações
        $SQLnoti= "select * from  notificacoes where lido='nao' and tipo='fatura' and admin='sim'";
        $SQLnoti = $conn->prepare($SQLnoti);
        $SQLnoti->execute();
        $totalnoti = $SQLnoti->rowCount();

        if($totalnoti>0){
        while($tudo= $SQLnoti->fetch()){

        $SQLnotiler= "update notificacoes set lido='sim' where id='".$tudo['id']."'";
        $SQLnotiler = $conn->prepare($SQLnotiler);
        $SQLnotiler->execute();

        }
        }

}elseif($ler=='contas'){

        // Notificações
        $SQLnoti= "select * from  notificacoes where lido='nao' and tipo='conta' and admin='sim'";
        $SQLnoti = $conn->prepare($SQLnoti);
        $SQLnoti->execute();
        $totalnoti = $SQLnoti->rowCount();

        if($totalnoti>0){
        while($tudo= $SQLnoti->fetch()){

        $SQLnotiler= "update notificacoes set lido='sim' where id='".$tudo['id']."'";
        $SQLnotiler = $conn->prepare($SQLnotiler);
        $SQLnotiler->execute();

        }
        }

}elseif($ler=='revenda'){

        // Notificações
        $SQLnoti= "select * from  notificacoes where lido='nao' and tipo='revenda' and admin='sim'";
        $SQLnoti = $conn->prepare($SQLnoti);
        $SQLnoti->execute();
        $totalnoti = $SQLnoti->rowCount();

        if($totalnoti>0){
        while($tudo= $SQLnoti->fetch()){

        $SQLnotiler= "update notificacoes set lido='sim' where id='".$tudo['id']."'";
        $SQLnotiler = $conn->prepare($SQLnotiler);
        $SQLnotiler->execute();

        }
        }

}elseif($ler=='chamados'){

        // Notificações
        $SQLnoti= "select * from  notificacoes where lido='nao' and tipo='chamados' and admin='sim'";
        $SQLnoti = $conn->prepare($SQLnoti);
        $SQLnoti->execute();
        $totalnoti = $SQLnoti->rowCount();

        if($totalnoti>0){
        while($tudo= $SQLnoti->fetch()){

        $SQLnotiler= "update notificacoes set lido='sim' where id='".$tudo['id']."'";
        $SQLnotiler = $conn->prepare($SQLnotiler);
        $SQLnotiler->execute();

        }
        }

}

}else{$tipo='tudo';}

?>
<style>
    .card-datatable {
        padding-left: 5px;
        padding-right: 5px;
    }
</style>
<section id="complex-header-datatable">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h4 class="card-title">Notificações</h4>
                </div>
                <div class="card-datatable">
                    <table id="example" class="table table-striped">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Informações</th>
                                <th>Detalhes</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if($tipo=='tudo'){
                                $SQLUPUser= "SELECT * FROM notificacoes where admin='sim' ORDER BY id desc LIMIT 15";
                            }else{
                                $SQLUPUser= "SELECT * FROM notificacoes where admin='sim' and tipo='".$tipo."' ORDER BY id desc LIMIT 15";
                            }
                            $SQLUPUser = $conn->prepare($SQLUPUser);
                            $SQLUPUser->execute();

                            // output data of each row
                            if (($SQLUPUser->rowCount()) > 0) {
                                while($noti = $SQLUPUser->fetch()){

                                    switch($noti['tipo']){
                                        case 'fatura':$tipo='Fatura';$class='label label-success';$info='Visualizar';break;
                                        case 'conta':$tipo='Conta';$class='label label-warning';$info=$noti['info_outros'];break;
                                        case 'revenda':$tipo='Revenda';$class='label label-danger';$info=$noti['info_outros'];break;
                                        case 'outros':$tipo='Info';$class='label label-primary';$info=$noti['info_outros'];break;
                                        case 'chamados':$tipo='Ticket';$class='label label-primary';$info='CONEXAO NET';break;
                                        default:$tipo='erro';break;
                                    }

                                    //Datas

                                    $datacriado=$noti['data'];
                                    $dataconvcriado = substr($datacriado, 0, 10);
                                    $partes = explode("-", $dataconvcriado);
                                    $ano = $partes[0];
                                    $mes = $partes[1];
                                    $dia = $partes[2];


                                    ?>

                                    <tr>
                                        <td><?php echo $noti['id'];?></td>
                                        <td><?php echo $tipo;?></td>
                                        <td><?php echo $dia;?>/<?php echo $mes;?>/<?php echo $ano;?></td>
                                        <?php if($tipo=='Fatura'){?>
                                            <td><a href="home.php?page=<?php echo $noti['linkfatura'];?>"><span class="<?php echo $class;?>"><?php echo $info;?></span></a></td>
                                        <?php }else{?>
                                            <td><span class="<?php echo $class;?>"><?php echo $info;?></span></td>
                                        <?php }?>
                                        <td><?php echo $noti['mensagem'];?></td>
                                    </tr>

                                <?php } }else{ ?>
                                <br>
                            <?php }?>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>