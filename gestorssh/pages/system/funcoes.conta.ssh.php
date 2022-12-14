<?php
require_once('seguranca.php');
require_once('config.php');
require_once('funcoes.php');
require_once('classe.ssh.php');
require_once("funcoes.system.php");

$diretorio = "";
$diretorio2 = "/admin/home.php?page=ssh/online";
$diretorio3 = "/home.php?page=ssh/online";

if(  isset($_POST["op"]) && isset($_POST["owner"])   ){
    $operacao = $_POST["op"];
    $owner = $_POST['owner'];

    if($owner != $accessKEY){
        protegePagina("user");

    }else if($owner == $accessKEY){
        protegePagina("admin");
    }


    //Alterar Senha SSH
    if($operacao == "senha" ){
        if((isset($_POST["id_servidor"])) &&
            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"])) && (
            isset($_POST["senha_ssh"])) )
        {
            //Variaveis utilizada

            $senha = $_POST['senha_ssh'];
            $id_usuarioSSH = $_POST["id_usuario_ssh"];
            $id_servidor = $_POST["id_servidor"];
            $diretorio = $_POST["diretorio"];
            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            if($usuario_ssh['demo']=='sim'){
                echo '<script type="text/javascript">';
                echo 	'alert("Não permitido alterar conta de demonstração!");';
                echo	'window.location="'.$diretorio.'";';
                echo '</script>';
                exit;
            }

            //Carrega servidor
            $SQLServidor = "select * from servidor WHERE id_servidor = '".$id_servidor."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor = $SQLServidor->fetch();

            //Caso nao seja admin, devemos verificar se ele realmente e o dono ou sub
            if($owner!= $accessKEY){
                //Carrega Owner
                $SQLOwner = "select * from usuario WHERE id_usuario = '".$owner."'";
                $SQLOwner = $conn->prepare($SQLOwner);
                $SQLOwner->execute();
                $owner = $SQLOwner->fetch();

                //Caso o owner do parametro nao seja realmente o owner da conta ssh
                if(!($usuario_ssh['id_usuario'] == $owner['id_usuario'])){
                    //Caso o owner do parametro seja revenda, devemos verificar de algun sub, seja owner da conta ssh
                    if($owner['tipo']=="revenda"){

                        //Carrega usuario owner da conta ssh parametro
                        $SQLSub = "select * from usuario WHERE id_usuario = '".$usuario_ssh['id_usuario']."' and id_mestre='".$owner['id_usuario']."' ";
                        $SQLSub = $conn->prepare($SQLSub);
                        $SQLSub->execute();
                        $usuario_ssh_owner = $SQLSub->fetch();
                        //Caso nao seja
                        if( !($usuario_ssh['id_usuario']==$usuario_ssh_owner['id_usuario'])){
                            echo myalertuser('error', 'Sem permissao !', $diretorio);
                            exit;
                        }

                    }else{
                        echo myalertuser('error', 'Sem permissao !', $diretorio);
                        exit;

                    }

                }



            }else if($owner==$accessKEY){
                $owner = 0;
            }

            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor['ip_servidor'];
            $loginSSH= $servidor['login_server'];
            $senhaSSH=  $servidor['senha'];
            $ssh = new SSH2($ip_servidor);
            $ssh->auth($loginSSH,$senhaSSH);

            $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --pass_user '.$usuario_ssh['login'].' '.$senha.' || ./AlterarSenha.sh '.$usuario_ssh['login'].' '.$senha.'');
            $mensagem = (string) $ssh->output();


            $SQLSSH = "update usuario_ssh set senha='".$senha."' WHERE id_usuario_ssh = '".$usuario_ssh['id_usuario_ssh']."'  ";
            $SQLSSH = $conn->prepare($SQLSSH);
            $SQLSSH->execute();

            echo myalertuser('success', 'Senha alterada com sucesso!', $diretorio);
            
        }else{
            echo myalertuser('error', 'Preencha todos os campos!', $diretorio);
        }
        //Alterar dias de acesso SSH
    }else if($operacao == "dias" ){
        if((isset($_POST["dias"])) &&
            (isset($_POST["id_usuarioSSH"])) &&
            (isset($_POST["diretorio"]))
        ){

            $id_usuarioSSH = $_POST["id_usuarioSSH"];
            $dias_acesso = 	$_POST["dias"];
            $diretorio = 	$_POST["diretorio"];
            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Carrega servidor
            $id_servidor =  $usuario_ssh['id_servidor'];
            $SQLServidor = "select * from servidor WHERE id_servidor = '".$usuario_ssh['id_servidor']."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor = $SQLServidor->fetch();

            if($owner != $accessKEY){
                if($usuario_ssh['id_usuario']!=$owner){
                    $SQLUsuarioSSH = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."'";
                    $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
                    $SQLUsuarioSSH->execute();
                    if (($SQLUsuarioSSH->rowCount()) < 0){
                        echo '<script type="text/javascript">';
                        echo 	'alert("Nao permitido!!");';
                        echo	'window.location="'.$diretorio.'";';
                        echo '</script>';
                        exit;

                    }


                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }
            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor['ip_servidor'];
            $loginSSH= $servidor['login_server'];
            $senhaSSH=  $servidor['senha'];
            $ssh = new SSH2($ip_servidor);
            $ssh->auth($loginSSH,$senhaSSH);

            $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --date_user '.$usuario_ssh['login'].' '.$dias_acesso.' || ./AlterarData.sh '.$usuario_ssh['login'].' '.$dias_acesso.'');
            $mensagem = (string) $ssh->output();

            $data_validade = date('Y-m-d', strtotime(' + '.$dias_acesso.'  days'));
            $SQLSSH = "update usuario_ssh set data_validade='".$data_validade."' WHERE id_usuario_ssh = '".$usuario_ssh['id_usuario_ssh']."'  ";
            $SQLSSH = $conn->prepare($SQLSSH);
            $SQLSSH->execute();

            echo myalertuser('success', 'Alterado com sucesso!', $diretorio);
            exit;
        }else{
            echo myalertuser('error', 'Preencha todos os campos!', $diretorio);
            exit;

        }

        //Alterar acesso simultaneo
    }else if($operacao == "acesso" ){

        if(
            (isset($_POST["diretorio"])) &&
            (isset($_POST["acesso"])) &&
            (isset($_POST["sistema"])) &&
            (isset($_POST["id_usuario_ssh"]))
        ){

            //Variaveis utilizada
            $id_usuarioSSH = $_POST["id_usuario_ssh"];
            $acesso = $_POST["acesso"];
            $diretorio = $_POST["diretorio"];
            $sistema = $_POST["sistema"];
            $valida = 0 ;
            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Carrega servidor
            $id_servidor =  $usuario_ssh['id_servidor'];
            $SQLServidor = "select * from servidor WHERE id_servidor = '".$usuario_ssh['id_servidor']."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor = $SQLServidor->fetch();

            //Verifica permissao
            if($owner != $accessKEY){
                if(!($usuario_ssh['id_usuario'] == $sistema)){
                    $SQLUsuarioSub= "select * from usuario WHERE id_mestre = '".$sistema."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUsuarioSub = $conn->prepare($SQLUsuarioSub);
                    $SQLUsuarioSub->execute();

                    if(!(($SQLUsuarioSub->rowCount()) > 0)){
                        echo '<script type="text/javascript">';
                        echo 	'alert("Voce nao tem permissao!");';
                        echo	'window.location="'.$diretorio.'";';
                        echo '</script>';
                        exit;
                    }else{
                        $valida = 1 ;
                    }
                }else{
                    $valida = 1 ;
                }
            }

            //Caso precise, verifica se existe acesso disponivel
            if($valida == 1){
                //Carrega usuario
                $SQLUser = "select * from usuario WHERE id_usuario = '".$sistema."'  ";
                $SQLUser = $conn->prepare($SQLUser);
                $SQLUser->execute();
                $usuario_sistema = $SQLUser->fetch();
                $quantidade_acesso_total = 0;

                //Carrega acesso servidor
                $SQLAcessoServidor = "select * from acesso_servidor WHERE id_usuario = '".$usuario_sistema['id_usuario']."' and id_servidor='".$servidor['id_servidor']."' ";
                $SQLAcessoServidor = $conn->prepare($SQLAcessoServidor);
                $SQLAcessoServidor->execute();
                $acesso_servidor = $SQLAcessoServidor->fetch();
                $limite_acesso = $acesso_servidor['qtd'];
                //Carrega acesso apenas da conta principal
                $SQLAcessoSSH = "select sum(acesso) AS quantidade  from usuario_ssh WHERE id_usuario = '".$usuario_sistema['id_usuario']."' and id_servidor='".$servidor['id_servidor']."' and id_usuario_ssh != '".$usuario_ssh['id_usuario_ssh']."' ";
                $SQLAcessoSSH = $conn->prepare($SQLAcessoSSH);
                $SQLAcessoSSH->execute();
                $SQLAcessoSSH = $SQLAcessoSSH->fetch();
                $quantidade_acesso_total += $SQLAcessoSSH['quantidade'];

                //Carrega acesso das subs contas
                //Carrega subs usuario
                $SQLSubUser = "select * from usuario WHERE id_mestre = '".$usuario_sistema['id_usuario']."'  ";
                $SQLSubUser = $conn->prepare($SQLSubUser);
                $SQLSubUser->execute();

                if (($SQLSubUser->rowCount()) > 0) {
                    while($row = $SQLSubUser->fetch()) {
                        $SQLAcessoSSH = "select sum(acesso) AS quantidade  from usuario_ssh WHERE id_usuario = '".$row['id_usuario']."' and id_servidor='".$servidor['id_servidor']."' and id_usuario_ssh != '".$id_usuarioSSH."' ";
                        $SQLAcessoSSH = $conn->prepare($SQLAcessoSSH);
                        $SQLAcessoSSH->execute();
                        $SQLAcessoSSH = $SQLAcessoSSH->fetch();
                        $quantidade_acesso_total += $SQLAcessoSSH['quantidade'];
                    }
                }

                $quantidade_acesso_total+=$acesso;

                if( $limite_acesso < $quantidade_acesso_total  ){
                    echo myalertuser('error', 'Sem limites no servidor!', $diretorio);
                    exit;
                }
            }

            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor['ip_servidor'];
            $loginSSH= $servidor['login_server'];
            $senhaSSH=  $servidor['senha'];
            $ssh = new SSH2($ip_servidor);
            $ssh->auth($loginSSH,$senhaSSH);

            $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --limit_user '.$usuario_ssh['login'].' '.$acesso.' || ./alterarlimite.sh '.$usuario_ssh['login'].' '.$acesso.'');
            $mensagem = (string) $ssh->output();

            if($mensagem == 4 ){
                $SQLSSH = "update usuario_ssh set acesso='".$acesso."' WHERE id_usuario_ssh = '".$usuario_ssh['id_usuario_ssh']."'  ";
                $SQLSSH = $conn->prepare($SQLSSH);
                $SQLSSH->execute();
            }

            //Classifica a mensagem de retorno
            switch($mensagem){
                case 0:
                    echo myalertuser('error', 'Erro no Servidor SSH!', $diretorio);
                    break;
                case 1:
                    echo myalertuser('error', 'Usuario invalido', $diretorio);
                    break;
                case 2:
                    echo myalertuser('error', 'Limite invalido', $diretorio);
                    break;
                case 3:
                    echo myalertuser('warning', 'Informe um numero maior que zero', $diretorio);
                    break;

                case 4:
                    echo myalertuser('success', 'Limite alterado com sucesso!', $diretorio);
                    break;

                case 5:
                    echo myalertuser('error', 'Usuario invalido', $diretorio);
                    break;
            }
        }else{

            echo myalertuser('warning', 'Preencha todos os campos!', $diretorio);
            exit;

        }


    }else if($operacao == "owner" ){

        if((isset($_POST["n_owner"])) &&
            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"]))
        )
        {
            $diretorio = $_POST["diretorio"];
            $id_usuario_ssh = $_POST["id_usuario_ssh"];
            $new_owner = $_POST["n_owner"];


            //Verifica se ele e Owner
            if($owner != $accessKEY){

                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuario_ssh."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();
                $UserSSH = $SQLUserSSH->fetch();
                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$UserSSH['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo myalertuser('error', 'sem permissao', $diretorio);
                        exit;
                    }
                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            $SQLSSH = "update usuario_ssh set id_usuario='".$new_owner."' WHERE id_usuario_ssh = '".$id_usuario_ssh."'   ";
            $SQLSSH = $conn->prepare($SQLSSH);
            $SQLSSH->execute();

            echo myalertuser('success', 'Alterado com sucesso!', $diretorio);
            exit;


        }else{

            echo '<script type="text/javascript">';
            echo 	'alert("Preencha todos os campos!");';
            echo	'window.location="'.$diretorio.'";';
            echo '</script>';
            exit;

        }

    }else if($operacao == "deletar"){
        if(
            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"]))
        ){
            $id_usuarioSSH = $_POST["id_usuario_ssh"];
            $diretorio = $_POST["diretorio"];

            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            if($usuario_ssh['demo']=='sim'){
                echo '<script type="text/javascript">';
                echo 	'alert("Não permitido alterar conta de demonstração!");';
                echo	'window.location="'.$diretorio.'";';
                echo '</script>';
                exit;
            }

            //Carrega usuario
            $SQLUser = "select * from usuario WHERE id_usuario = '".$owner."'  ";
            $SQLUser = $conn->prepare($SQLUser);
            $SQLUser->execute();
            $usuario_sistema = $SQLUser->fetch();

            //Verifica se ele e Owner
            if($owner != $accessKEY){
                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();

                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $UserSSH = $SQLUserSSH->fetch();
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo '<script type="text/javascript">';
                        echo 	'alert("Voce nao tem permissao!");';
                        echo	'window.location="'.$diretorio.'";';
                        echo '</script>';
                        exit;
                    }
                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            if($owner==0){
                if($usuario_ssh['id_usuario']<>0){
                    //Insere notificacao
                    $sshacc=$usuario_ssh['login'];
                    $usuarioid=$usuario_ssh['id_usuario'];
                    $msg="A Conta SSH <small><b>".$sshacc."</b></small> foi encerrada!";
                    $notins = "INSERT INTO notificacoes (usuario_id,data,tipo,linkfatura,mensagem,info_outros) values ('".$usuarioid."','".date('Y-m-d H:i:s')."','conta','n/d','".$msg."','Encerramento')";
                    $notins = $conn->prepare($notins);
                    $notins->execute();
                }
            }else{
                if($usuario_ssh['id_usuario']<>0){
                    //Insere notificacao
                    $sshacc=$usuario_ssh['login'];
                    $usuarioid=$usuario_ssh['id_usuario'];
                    $msg="Você acabou de encerrar a Conta SSH <small><b>".$sshacc."</b></small>!";
                    $notins = "INSERT INTO notificacoes (usuario_id,data,tipo,linkfatura,mensagem,info_outros) values ('".$usuarioid."','".date('Y-m-d H:i:s')."','conta','n/d','".$msg."','Encerramento')";
                    $notins = $conn->prepare($notins);
                    $notins->execute();
                }
            }

            $SQLDelSSH = "update usuario_ssh set status='3', apagar='3', id_usuario='0' WHERE id_usuario_ssh = '".$id_usuarioSSH."'  ";
            $SQLDelSSH = $conn->prepare($SQLDelSSH);
            $SQLDelSSH->execute();
            echo myalertuser('success', 'Usuario deletado!', $diretorio);
            exit;

        }else{
            echo myalertuser('warning', 'Preencha todos os campos!', $diretorio);
            exit;

        }
		
		}else if($operacao == "deletarsusp"){
            $id_usuarioSSH = $_POST["id_usuario_ssh"];
            $diretorio = $_POST["diretorio"];

            //Carrega usuarioSSH
			if (!empty($id_usuarioSSH)) {
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
			$usuario_ssh = $SQLUsuarioSSH->fetch();
				
			$SQLDelSSH = "update usuario_ssh set status='3', apagar='3', id_usuario='0' WHERE status >= '2'";
            $SQLDelSSH = $conn->prepare($SQLDelSSH);
            $SQLDelSSH->execute();
            echo myalertuser('success', 'Contas deletadas!', $diretorio);
            exit;


        }else{
            echo myalertuser('warning', 'Não tem contas suspensas!', $diretorio);
            exit;

        }
    }else if($operacao == "migrar"){
        if(

            (isset($_POST["id_ssh"])) &&
            (isset($_POST["id_new_servidor"])) &&
            (isset($_POST["diretorio"]))
        ){

            $diretorio = $_POST["diretorio"];
            $id_ssh = $_POST["id_ssh"];
            $id_new_servidor = $_POST["id_new_servidor"];

            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_ssh."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Carrega servidor
            $SQLServidor = "select * from servidor WHERE id_servidor = '".$usuario_ssh['id_servidor']."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor_old = $SQLServidor->fetch();

            //Carrega usuario
            $SQLUser = "select * from usuario WHERE id_usuario = '".$owner."'  ";
            $SQLUser = $conn->prepare($SQLUser);
            $SQLUser->execute();
            $usuario_sistema = $SQLUser->fetch();


            //Verifica se ele e Owner
            if($owner != $accessKEY){

                $SQLservidoral = "select * from  acesso_servidor WHERE 	id_servidor = '".$id_new_servidor."' and id_usuario='".$owner."' ";
                $SQLservidoral = $conn->prepare($SQLservidoral);
                $SQLservidoral->execute();

                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_ssh."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();

                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $UserSSH = $SQLUserSSH->fetch();
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo '<script type="text/javascript">';
                        echo 	'alert("Voce nao tem permissao!");';
                        echo	'window.location="'.$diretorio.'";';
                        echo '</script>';
                        exit;

                    }

                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            if($owner==0){
                if($usuario_ssh['id_usuario']<>0){
                    //Insere notificacao
                    $sshacc=$usuario_ssh['login'];
                    $usuarioid=$usuario_ssh['id_usuario'];
                    $msg="O sistema acabou de Migrar a Conta SSH <small><b>".$sshacc."</b></small> para o Servidor <small><b>".$servidor_old['nome']."</b></small>  !";
                    $notins = "INSERT INTO notificacoes (usuario_id,data,tipo,linkfatura,mensagem,info_outros) values ('".$usuarioid."','".date('Y-m-d H:i:s')."','conta','n/d','".$msg."','Encerramento')";
                    $notins = $conn->prepare($notins);
                    $notins->execute();
                }
            }else{
                if($usuario_ssh['id_usuario']<>0){
                    //Insere notificacao
                    $sshacc=$usuario_ssh['login'];
                    $usuarioid=$usuario_ssh['id_usuario'];
                    $msg="Você migrou a Conta SSH <small><b>".$sshacc."</b></small> para o Servidor <small><b>".$servidor_old['nome']."</b></small>  !";
                    $notins = "INSERT INTO notificacoes (usuario_id,data,tipo,linkfatura,mensagem,info_outros) values ('".$usuarioid."','".date('Y-m-d H:i:s')."','conta','n/d','".$msg."','Encerramento')";
                    $notins = $conn->prepare($notins);
                    $notins->execute();
                }
            }


            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor_old['ip_servidor'];
            $loginSSH= $servidor_old['login_server'];
            $senhaSSH=  $servidor_old['senha'];
            $ssh = new SSH2($ip_servidor);
            $ssh->auth($loginSSH,$senhaSSH);

            $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --del_user '.$usuario_ssh['login'].' || ./remover.sh '.$usuario_ssh['login'].'');
            $mensagem = (string) $ssh->output();

            $SQLServidor = "select * from servidor WHERE id_servidor = '".$_POST['id_new_servidor']."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor_new = $SQLServidor->fetch();

            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor_new['ip_servidor'];
            $loginSSH= $servidor_new['login_server'];
            $senhaSSH=  $servidor_new['senha'];
            $ssh_new = new SSH2($ip_servidor);
            $ssh_new->auth($loginSSH,$senhaSSH);

            $datauser = date_create($usuario_ssh['data_validade']);
            $datahoje = date_create();
            $result = date_diff($datauser, $datahoje);
            $mdays = date_interval_format($result, '%a');
            
            $ssh_new->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --create_user '.$usuario_ssh['login']. ' ' .$usuario_ssh['senha']. ' ' .$usuario_ssh['acesso']. ' ' .$mdays. ' || ./criarusuario.sh  '.$usuario_ssh['login']. ' ' .$usuario_ssh['senha']. ' ' .$usuario_ssh['acesso']. ' ' .$mdays. '');
            $mensagem_new = (string) $ssh_new->output();

            if($mensagem_new == 13){
                $SQLDelSSH = "update usuario_ssh set id_servidor='".$_POST['id_new_servidor']."'  WHERE id_usuario_ssh = '".$usuario_ssh['id_usuario_ssh']."'  ";
                $SQLDelSSH = $conn->prepare($SQLDelSSH);
                $SQLDelSSH->execute();
                $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --del_user '.$usuario_ssh['login'].' || ./remover.sh '.$usuario_ssh['login'].'');
                $ssh->output();
                echo myalertuser('success', 'Migrado com sucesso!', $diretorio);
                exit;

            }else{
                $ssh_new->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --del_user '.$login_ssh.' || ./remover.sh '.$login_ssh.'');
                $ssh_new->output();

                echo myalertuser('warning', 'Erro no servidor!', $diretorio);
                exit;
            }
        }else{
            echo myalertuser('warning', 'Preencha todos os campos', $diretorio);
            exit;

        }
    }else if($operacao == "kill"){
        if(

            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"]))
        ){

            $diretorio = $_POST["diretorio"];
            $id_ssh = $_POST["id_usuario_ssh"];

            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_ssh."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Carrega usuarioSSH
            $SQLServidor = "select * from servidor WHERE id_servidor = '".$usuario_ssh['id_servidor']."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor = $SQLServidor->fetch();

            //Carrega usuario
            $SQLUser = "select * from usuario WHERE id_usuario = '".$owner."'  ";
            $SQLUser = $conn->prepare($SQLUser);
            $SQLUser->execute();
            $usuario_sistema = $SQLUser->fetch();

            //Verifica se ele e Owner
            if($owner != $accessKEY){
                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_ssh."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();

                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $UserSSH = $SQLUserSSH->fetch();
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo myalertuser('warning', 'Erro!', $diretorio2);
                        exit;
                    }
                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor['ip_servidor'];
            $loginSSH= $servidor['login_server'];
            $senhaSSH=  $servidor['senha'];
            $ssh = new SSH2($ip_servidor);
            $ssh->auth($loginSSH,$senhaSSH);

            $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --kill_user '.$usuario_ssh['login'].' || ./KillUser.sh '.$usuario_ssh['login'].'');
            $mensagem = (string) $ssh->output();

            echo myalertuser('success', 'Usuário Desconectado!', $diretorio2);
            exit;



        }else{
            echo myalertuser('warning', 'Preencha todos os campos!', $diretorio2);
            exit;

        }
		}else if($operacao == "kill2"){
        if(

            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"]))
        ){

            $diretorio = $_POST["diretorio"];
            $id_ssh = $_POST["id_usuario_ssh"];

            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_ssh."'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Carrega usuarioSSH
            $SQLServidor = "select * from servidor WHERE id_servidor = '".$usuario_ssh['id_servidor']."'";
            $SQLServidor = $conn->prepare($SQLServidor);
            $SQLServidor->execute();
            $servidor = $SQLServidor->fetch();

            //Carrega usuario
            $SQLUser = "select * from usuario WHERE id_usuario = '".$owner."'  ";
            $SQLUser = $conn->prepare($SQLUser);
            $SQLUser->execute();
            $usuario_sistema = $SQLUser->fetch();

            //Verifica se ele e Owner
            if($owner != $accessKEY){
                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_ssh."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();

                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $UserSSH = $SQLUserSSH->fetch();
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo myalertuser('warning', 'Erro!', $diretorio3);
                        exit;
                    }
                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            //Realiza a comunicacao com o servidor
            $ip_servidor= $servidor['ip_servidor'];
            $loginSSH= $servidor['login_server'];
            $senhaSSH=  $servidor['senha'];
            $ssh = new SSH2($ip_servidor);
            $ssh->auth($loginSSH,$senhaSSH);

            $ssh->exec('[[ -f "/opt/sshplus/sshplus" ]] && /opt/sshplus/plugin-sync --kill_user '.$usuario_ssh['login'].' || ./KillUser.sh '.$usuario_ssh['login'].'');
            $mensagem = (string) $ssh->output();

            echo myalertuser('success', 'Usuário Desconectado!', $diretorio3);
            exit;



        }else{
            echo myalertuser('warning', 'Preencha todos os campos!', $diretorio3);
            exit;

        }
    }else if($operacao == "suspender"){
        if(
            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"]))
        ){
            $id_usuarioSSH = $_POST["id_usuario_ssh"];
            $diretorio = $_POST["diretorio"];

            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."' and status='1' and apagar='0'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Verifica se ele e Owner
            if($owner != $accessKEY){
                //Carrega usuario
                $SQLUser = "select * from usuario WHERE id_usuario = '".$owner."'  ";
                $SQLUser = $conn->prepare($SQLUser);
                $SQLUser->execute();
                $usuario_sistema = $SQLUser->fetch();


                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();

                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $UserSSH = $SQLUserSSH->fetch();
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo myalertuser('warning', 'Erro - sem permissao!', $diretorio);
                        exit;
                    }
                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            if($owner==0){
                if($usuario_ssh['id_usuario']<>0){
                    //Insere notificacao
                    $sshacc=$usuario_ssh['login'];
                    $usuarioid=$usuario_ssh['id_usuario'];
                    $msg="A Conta SSH <small><b>".$sshacc."</b></small>! acabou de ser suspensa!";
                    $notins = "INSERT INTO notificacoes (usuario_id,data,tipo,linkfatura,mensagem,info_outros) values ('".$usuarioid."','".date('Y-m-d H:i:s')."','conta','n/d','".$msg."','Encerramento')";
                    $notins = $conn->prepare($notins);
                    $notins->execute();
                }
            }else{
                if($usuario_ssh['id_usuario']<>0){
                    //Insere notificacao
                    $sshacc=$usuario_ssh['login'];
                    $usuarioid=$usuario_ssh['id_usuario'];
                    $msg="Você acabou de Suspender a Conta SSH <small><b>".$sshacc."</b></small>!";
                    $notins = "INSERT INTO notificacoes (usuario_id,data,tipo,linkfatura,mensagem,info_outros) values ('".$usuarioid."','".date('Y-m-d H:i:s')."','conta','n/d','".$msg."','Encerramento')";
                    $notins = $conn->prepare($notins);
                    $notins->execute();
                }
            }

            $SQLDelSSH = "update usuario_ssh set status='2', apagar='2' WHERE id_usuario_ssh = '".$id_usuarioSSH."'  ";
            $SQLDelSSH = $conn->prepare($SQLDelSSH);
            $SQLDelSSH->execute();

            echo myalertuser('success', 'Usuario ssh suspendido!', $diretorio);
            exit;

        }else{
            echo myalertuser('warning', 'Erro - sem solicitacao!', $diretorio);
            exit;

        }
    }else if($operacao == "ususpender"){
        if((isset($_POST["owner"])) &&
            (isset($_POST["id_usuario_ssh"])) &&
            (isset($_POST["diretorio"]))
        ){
            $id_usuarioSSH = $_POST["id_usuario_ssh"];
            $diretorio = $_POST["diretorio"];
            $owner = $_POST["owner"];

            //Carrega usuarioSSH
            $SQLUsuarioSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."' and status='2' and apagar='0'";
            $SQLUsuarioSSH = $conn->prepare($SQLUsuarioSSH);
            $SQLUsuarioSSH->execute();
            $usuario_ssh = $SQLUsuarioSSH->fetch();

            //Verifica se ele e Owner
            if($owner != $accessKEY){
                //Carrega usuario
                $SQLUser = "select * from usuario WHERE id_usuario = '".$owner."'  ";
                $SQLUser = $conn->prepare($SQLUser);
                $SQLUser->execute();
                $usuario_sistema = $SQLUser->fetch();

                $SQLUserSSH = "select * from usuario_ssh WHERE id_usuario_ssh = '".$id_usuarioSSH."' and id_usuario='".$owner."' ";
                $SQLUserSSH = $conn->prepare($SQLUserSSH);
                $SQLUserSSH->execute();

                //Caso nao seja owner
                if(!(($SQLUserSSH->rowCount()) > 0)){
                    $UserSSH = $SQLUserSSH->fetch();
                    $SQLUser = "select * from usuario WHERE id_mestre = '".$owner."' and id_usuario='".$usuario_ssh['id_usuario']."' ";
                    $SQLUser = $conn->prepare($SQLUser);
                    $SQLUser->execute();
                    //Caso nao seja sub do owner
                    if(!(($SQLUser->rowCount()) > 0)){
                        echo myalertuser('warning', 'Erro - Permissao negada!', $diretorio);
                        exit;

                    }
                }

            }else if($owner==$accessKEY){
                $owner = 0;
            }

            $SQLDelSSH = "update usuario_ssh set status='1', apagar='1' WHERE id_usuario_ssh = '".$id_usuarioSSH."'  ";
            $SQLDelSSH = $conn->prepare($SQLDelSSH);
            $SQLDelSSH->execute();
            echo myalertuser('success', 'Usuario SSH liberado!', $diretorio);
            exit;

        }else{
            echo myalertuser('warning', 'Erro - sem solicitacao!', $diretorio);
            exit;
        }
    }else{
        echo myalertuser('warning', 'Erro - sem solicitacao!', $diretorio);
        exit;
    }
}


?>
