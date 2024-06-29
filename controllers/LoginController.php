<?php 

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

Class LoginController { 
    public static function login(Router $router)  { 

        $alertas = [];

        if($_SERVER['REQUEST_METHOD']  === 'POST') { 

           // echo "Desde POST..";

           $auth = new Usuario($_POST);
           $alertas = $auth -> validarLogin();

           if(empty($alertas)) {  

            //echo "usuario agrego tanto email como password";
            // Comprobar que existe el usuario
            $usuario = Usuario::where('email' , $auth->email);
            
            if($usuario){ 
                // Verificar el Password
                if($usuario->comprobarPasswordAndVerificado($auth->password)) { 
                    // Autenticar el usuario
                    session_start();

                    $_SESSION['id'] = $usuario->id;
                    $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                    $_SESSION['email'] = $usuario->email;
                    $_SESSION['login'] = true;

                    // Redireciionamiento
                        if($usuario->admin === "1") { 

                            // debuguear("Es Admin...");
                            $_SESSION['admin'] = $usuario->admin ?? null;

                             header('Location: /admin');

                        } else {   

                            // debuguear("Es Cliente...");
                            header('Location: /cita');


                        }


                }

            } else {  

                Usuario::setAlerta('error' , 'Usuario no encontrado');
            }

           }
          
        }

        $alertas = Usuario::getAlertas();

       $router->render('auth/login' , [ 

            'alertas' => $alertas
       ]);
    }

    public static function logout() { 

       // echo "Desde Logout ...";
       
        $_SESSION = [];
        header('Location: /');


    }

    public static function olvide(Router $router) { 

        //echo "Desde Olvide ...";
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') { 
          
            $auth = new Usuario($_POST);
            $alertas = $auth  -> validarEmail();
           
            if(empty($alertas)) {  

                $usuario = Usuario::where('email' , $auth->email);
             

                if($usuario && $usuario->confirmado === "1") {  
                    
                    // Generar un token
                    $usuario->crearToken();
                    $usuario->guardar();
                    
                    // Enviar el email
                    $email = new Email($usuario->email , $usuario->nombre , $usuario->token);
                    $email->enviarInstrucciones();


                    // Alerta de exito 
                    Usuario::setAlerta('exito' , 'Revisa tu e-mail');
                    

                } else {  

                    Usuario::setAlerta('error' , 'El usaurio no existe o no esta confirmado');
                    
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide-password' , [
            'alertas' => $alertas
            
        ]);
    }

    public static function recuperar(Router $router) { 

       // echo "Desde Recuperar ...";

       $alertas = [];
       $error = false;

       $token = s($_GET['token']);

       // Buscar usuario por su token

       $usuario = Usuario::where('token' , $token);

       if(empty($usuario)) {  

        Usuario::setAlerta('error' , 'Token no válido');
        $error = true;
       }

       if($_SERVER['REQUEST_METHOD'] === 'POST') {  

        // Leer el nuevo Password y guardarlo
        $password = new Usuario($_POST);
        $alertas =  $password -> validarPassword();

        if(empty($alertas)) {  
           
            $usuario->password = null;

           
            $usuario->password = $password->password;
            $usuario->hashPassword();
            $usuario->token = null;

           $resultado = $usuario->guardar();
          
           if($resultado) { 

            header('Location: /');

           }
            
        }

       }

       $alertas = Usuario::getAlertas();

       $router->render('auth/recuperar-password' , [ 
        
        'alertas' => $alertas,
        'error' => $error

       ]);
    }

    public static function crear(Router $router) { 

       // echo "Desde Crear ...";
       $usuario = new Usuario($_POST);

       // Alertas vacias
       $alertas = [];

       if($_SERVER['REQUEST_METHOD'] === 'POST') {

        // echo "enviaste el formulario";

        $usuario -> sincronizar($_POST);
        $alertas = $usuario -> validarNuevaCuenta();

           // Revisar que $alertas este vacio
           if(empty($alertas)) { 
           // echo "Pasaste la validación";

            // Verificar que el usuario no este registrado
           $resultado = $usuario->existeUsuario();
            if($resultado->num_rows) { 
                $alertas = Usuario::getAlertas();

            } else {  
                // No esta registrado
                // Hashear eñ password para ingresarlo 
                $usuario->hashPassword();

                // Generar un Token único
                $usuario->crearToken();

                // Enviar el Email

                $email = new Email($usuario->email, $usuario->nombre , $usuario->token);

                $email -> enviarConfirmacion();

                // Crear el Usuario

                $resultado = $usuario->guardar();

                if($resultado) {  

                   header('Location: /mensaje');
                }

                
            }


           }
       }


       $router->render('auth/crear-cuenta' , [
        'usuario'=>$usuario,
        'alertas'=>$alertas
       ]);
    }

    public static function mensaje(Router  $router) { 

        $router->render('auth/mensaje');

    }


    public static function confirmar(Router $router) { 

        $alertas = [];
        $token = s($_GET['token']);

       $usuario = Usuario::where('token' , $token);

      if(empty($usuario)) {  

        // Mostrar mensaje de error
        Usuario::setAlerta('error' , 'Token no válido');
      } else {  
        // Modificar a usaurios confirmados
        
        $usuario->confirmado = "1";
        $usuario->token = null;
        $usuario->guardar();

        Usuario::setAlerta('exito' , 'Cuenta Comprobada Correctamente');
      }

        $alertas = Usuario::getAlertas(); // Para que tome la alerta que defini en Usuario::setAlerta

        // Renderizar la vista
        $router->render('auth/confirmar-cuenta' , [
            'alertas' => $alertas

        ]);
    }

}