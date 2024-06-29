<?php

namespace Controllers;

use Model\Cita;
use Model\CitaServicio;
use Model\Servicio;

class APIController { 

    public static function index() { 

       $servicios = Servicio::all();

       echo json_encode($servicios);
    }


public static function guardar() { 

    // Almacena la cita y devuelve el Id
     $cita = new Cita($_POST);
     $resultado = $cita->guardar();

     $id = $resultado['id'];
   
   // Almacena la cita y el servicio
   //Almacena los servicios con el Id de la Cita
   $idServicios = explode(",", $_POST['servicios'] ); // Crea un arreglo de los elementos de idServicios que estan separados por coma
   
   foreach($idServicios as $idServicio) { 
    $args = [  
        'citaId' => $id,
        'servicioId' => $idServicio
    ];
        $citaServicio = new CitaServicio($args);
        $citaServicio -> guardar();
   }
  
   
       echo json_encode(['resultado' => $resultado]);
}

public static function eliminar() {  

   // debuguear($_POST);
   // echo "Eliminando Cita...";

    if($_SERVER['REQUEST_METHOD'] === 'POST') { 

       $cita = Cita::find($_POST['id']);
       $cita->eliminar();
       header('Location:' . $_SERVER['HTTP_REFERER']);
    }

}


}