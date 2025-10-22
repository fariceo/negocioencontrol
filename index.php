<meta charset="UTF-8">
<meta charset="UTF-8">
<meta charset="UTF-8" name="viewport" content="width=device-width">
<?php
session_start();
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script>

    $(document).ready(function () {
        $("#categorias").hide();
        let lastScrollTop = 0;  // Variable para almacenar la posición del scroll anterior

        $("#muestra_menu").scroll(function () {
            let currentScrollTop = $("#muestra_menu").scrollTop();  // Obtener la posición actual del scroll

            if (currentScrollTop < lastScrollTop) {
                // Scroll hacia arriba
               // $("#categorias").fadeIn();  // Mostrar el div
               // $("#encabezado").fadeIn();  // Mostrar el div
                $("#footer").fadeIn();  // Mostrar el div

                // $('#footer').css('opacity','0');

            } else {
                // Scroll hacia abajo
               // $("#categorias").fadeOut();  // Ocultar el div
              //  $("#encabezado").fadeOut();  // Ocultar el div
                $("#footer").fadeOut();  // Ocultar el div
            }

            lastScrollTop = currentScrollTop;  // Actualizar la posición del scroll anterior
        });


        setInterval(function () {
            // Aquí va el código que deseas ejecutar continuamente

            var usuario = $("#usuario").val();

            $.ajax({

                type: 'POST',
                //url:'menu_clientes.php',
                url: 'asi_sistema/info/procesar2.php',
                data: { articulos: 1, articulos_usuario: usuario },
                success: function (result) {
                    $("#n_articulos").html(result);
                    //$("#menu_carta").css("display","none");
                }

            });


        }, 2000);



        // var contenidoCargado = false; // Para evitar cargar el contenido varias veces
        //$("#footer").css('opacity', '0');


        $(window).scroll(function () {
            var divPos = $('#footer').offset().top; // Posición del div
            var divHeight = $('#footer').outerHeight(); // Altura del div
            var windowHeight = $(window).height(); // Altura de la ventana
            var scrollPos = $(window).scrollTop(); // Posición de desplazamiento actual

            // Verificar si el div ha llegado a la mitad de la pantalla
            // if (scrollPos + windowHeight / 2 >= divPos + divHeight / 2 && !contenidoCargado) {
            if (scrollPos + windowHeight / 2 >= divPos + divHeight / 2) {
                // contenidoCargado = true; // Evitar que se vuelva a cargar
                console.log('El div ha llegado a la mitad de la pantalla.');

                // Cargar más contenido o incluir un documento
                //  $('#footer').css('opacity','1');


                // Ejemplo: cargar contenido desde un documento externo (por ejemplo, 'contenido.html')
                // $('#footer').load('contenido.html');
            }
        });

        ///////////

    });



    function categorias_menu(e, f) {
        //alert(e)


        $.ajax({
            type: 'POST',
            //url:'menu_clientes.php',
            url: 'index.php',
            data: { categoria: e, usuario: f },
            success: function (result) {
                $("body	").html(result);
                //$("#menu_carta").css("display","none");
                $("#categorias").fadeOut();

               // $("#encabezado").fadeOut();  // Ocultar el div
                // $("#footer").fadeOut();  // Ocultar el div
            }

        });
    }



    function agregar(e, f, g, h) {
        //alert (e+f);

        if ($("#usuario").val() == "") {

            alert("Debes Introducir un ID de pedido para identificar la Orden");
           ("#usuario").focus();
        } else {


            var cantidad = prompt("introduce cantidad para " + e);


            //alert(e+f+g+h)
            var total = g * cantidad;
            $.ajax({
                type: "POST",
                url: "index.php",
                data: { usuario: f, producto: e, cantidad: cantidad, precio: g, categoria: h, total: total },
                success: function (result) {
                    $("body").html(result);

                }


            });


            $.ajax({
                type: "POST",
                url: "asi_sistema/info/procesar2.php",
                data: { producto: e, cantidad: cantidad, restar_stock: 1 },
                success: function (result) {



                }

            });
        }


    }




    function usuario(e) {
        var usuario = $("#usuario").val();


        $.ajax({

            type: "POST",
            url: "index.php",
            data: { usuario: $("#usuario").val(), inicio_sesion: 1 },
            success: function (result) {

                //$("body").html(result);
                if (usuario == 'volantuso') {

                    //  $("body").load("admin.php");
                } else {

                    // $("body").html(result);
                }
                //$("body").html(result);
                location.href = "index.php";
            }
        });
    }
function cambiar_usuario() {
    $.ajax({
        type: "POST",
        url: "index.php",
        data: { cambiar_usuario: 1 },
        success: function (result) {
            console.log("Respuesta del servidor:", result);
            // Redirigir al index si todo está bien
            location.href = "index.php";
        },
        error: function () {
            alert("❌ Error al cerrar sesión.");
        }
    });
}


    function eliminar_producto(e, f, g) {
        //alert(g);
        $.ajax({
            type: "POST",
            url: "index.php",
            data: { eliminar_id: e, seccion: f, categoria: f, nombre_archivo_servidor: g },
            success: function (result) {
                $("body").html(result);
            }

        });
    }


    function comandas(e) {


        $.ajax({
            type: "POST",
            url: "comandas.php",
            data: { usuario: e },
            success: function (result) {
                $("body").html(result)
            }


        });
    }

    function detalles(e, f, g) {
        // alert(e)
        // alert(f)
        var detalles = prompt("Escribir una descripcion del plato");


        if (detalles != "") {


            $.ajax({
                type: "POST",
                url: "asi_sistema/info/procesar2.php",
                data: { detalles: detalles, id_detalles_plato: f },
                success: function (result) {
                    $("body	").html(result);
                }


            });
            $.ajax({
                type: 'POST',
                //url:'menu_clientes.php',
                url: 'index.php',
                data: { categoria: f, usuario: g },
                success: function (result) {

                    //$("#menu_carta").css("display","none");
                }

            });
        } else {

            detalles = prompt("Escribir una descripcion del plato");
        }
    }

    function tiempo_aprox(e, f, g) {

        // alert(e)
        var tiempo_aprox = prompt("Elegir el tiempo aproximado de preparacion");

        //var timing = "00:" + tiempo_aprox + ":00";


        $.ajax({
            type: "POST",
            url: "asi_sistema/info/procesar2.php",
            data: { tiempo_aprox_id: e, tiempo_aprox: tiempo_aprox },
            success: function (result) {
                // $("body").html(result)
            }


        });

        $.ajax({
            type: 'POST',
            //url:'menu_clientes.php',
            url: 'index.php',
            data: { categoria: f, usuario: g },
            success: function (result) {
                $("body	").html(result);
                //$("#menu_carta").css("display","none");
            }

        });
    }


    function test() {
        $("#contenedor").html("hola");
    }

    function historial_compra() {
        /// alert(1)
        $.ajax({

            type: 'POST',
            //url:'menu_clientes.php',
            url: 'asi_sistema/info/usuarios/saldo_clientes.php',
            data: { historial_cliente: "<?php echo $_SESSION['usuario'] ?>" },
            success: function (result) {
                $("#contenedor").html(result);
                //$("#menu_carta").css("display","none");
            }

        });
    }

    function ver_deuda(e) {
        //alert(e)
        $.ajax({

            type: 'POST',
            //url:'menu_clientes.php',
            url: 'asi_sistema/info/usuarios/saldo_clientes.php',
            data: { deuda_cliente: e },
            success: function (result) {
                $("#contenedor").html(result);
                //$("#menu_carta").css("display","none");
            }

        });
    }

   

    $(document).on("click", ".hamburger", function () {
        $("#categorias").fadeIn();
        });






        ///detalles del plato
        function descripcion_plato(platoId) {
        $.ajax({
            type: 'POST',
            url: 'asi_sistema/info/procesar2.php',
            data: {get_descripcion_plato: platoId}, // Send the ID to identify the dish
            success: function (result) {
              mostrar_descripcion_en_menu(result,platoId);
            }
        });
    }

    function mostrar_descripcion_en_menu(descripcion, platoId){

        var muestraMenu = $("#muestra_menu");
        muestraMenu.empty();//clear the html

        var botonVolver = $('<button>').text("Back to Menu").click(function () {
            categorias_menu($('#categoria_actual').val(), '<?php echo $_SESSION['usuario'] ?>');
        });

        // Get the image from the plate
        var imagenPlato = $('table[data-plato-id="' + platoId + '"] img').attr('src');

        // Create image element
        var imgElement = $('<img>').attr('src', imagenPlato);

        // Style the image
        imgElement.css({
            'width': '150px',
            'height': '150px',
            'border-radius': '5%',
            'margin': '25px'
        });
          // Add img in the div muestra menu
        muestraMenu.append(imgElement);
         //Create a div for the descripcion
        var descripcionElement = $('<div>').html(descripcion);
          // Add the descripcion to the div muestra menu
        muestraMenu.append(descripcionElement);
          //Add margin to the text
        descripcionElement.css('margin', '25px');
           //Add the button
        muestraMenu.append(botonVolver);


    }

     // Event delegation for click on .detalles

     /*
     $(document).on("click", ".detalles", function (event) {
         event.preventDefault();
        descripcion_plato();
     });*/

       // Event delegation for click on .detalles
    $(document).on("click", ".detalles", function (event) {
        event.preventDefault();
        var platoId = $(this).closest('table').data('plato-id');
        descripcion_plato(platoId);
    });
</script>


<style>
    a {
        text-decoration: none;
    }
table{
    border-collapse: collapse;
         border: 1px solid #d3a516 ;
         
         background:white  ;
         margin: auto;
         font-family: sans-serif;
         border-spacing: 1;
}
   
        /* Estilos para el menú hamburguesa */
        .hamburger {
      cursor: pointer;
      width: 30px;
      height: 30px;
      position: absolute; /* Posicionar el menú hamburguesa */
      left: 10px; /* Ajustar la posición del menú hamburguesa */
      top: 10%; /* Ajustar la posición del menú hamburguesa */
      z-index: 1001; /* Asegurarse de que esté por encima de otros elementos */
      margin-right:25%;

      float:right;
    }
    .hamburger .line {
      width: 100%;
      height: 3px;
      background-color: black;
      display: block;
      margin: 5px 0;
      transition: all 0.3s ease-in-out;
  
     
    }

    tr {
     border: 2px #937200; /* Adjust the thickness (2px) and color (brown) as needed */
     border-radius:5px;
     margin-bottom:10px;
     border-collapse: collapse;
     //background-color: #a4d6ff ;
    }

    #categorias{

        position:fixed;
        left:0;
        top:50%;
        transform: translateY(-50%);
        display: flex;
        flex-direction: column;
        padding: 10px;
        z-indez:1000;
        display:table;
        background: black;
        opacity:0.8;

    }
    #categorias button {
    display: block; /* Make buttons block-level for vertical stacking */
    width: 100%; /* Make buttons take up the full width of the container */
    margin-bottom: 5px; /* Add space between buttons */
    padding: 10px;
    text-align: left; /* Optional: Left-align button text */
}


#whatsap{
        position: fixed; /* Make the element fixed */
        top: 60%; /* Distance from the bottom */
      float:right; /* Distance from the right */
      right:10px;
        z-index: 1000; /* Ensure it's above other elements */
        /* Optional: Add some background and padding for better visibility */
       
        padding: 15px;
        border-radius: 50px; /* Make it circular */
        //display: flex; /* Use flexbox for alignment */
       // align-items: center; /* Center items vertically */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Add a subtle shadow */
        background:white;
        
    }

    #muestra_menu{

      
    }


    .price-circle {
    display: inline-block; /* Make the span behave like a block for width/height */
    width: 45px; /* Adjust the size of the circle */
    height: 45px; /* Adjust the size of the circle */
    border-radius: 50%; /* Make it a circle */
    border: 2px solid #ffcc00; /* Adjust the color and thickness of the border */
    text-align: center; /* Center the text horizontally */
    line-height: 40px; /* Center the text vertically (same as height) */
    font-weight: bold; /* Make the price stand out */
    color: #8b5d02 ;/*text color*/


 
    box-shadow: 0px 2px 10px rgba(0,0,0,0.2);/* add shadow to circle*/

     /*  background-color: #dfdfdf;  /* Optionally, add a background color */
 
    background: linear-gradient(to bottom,#bdbdbd, #ffffff ); /* Gradient background */
   // background-color: #525252; /* Optionally, add a background color */
}


.detalles {
  display: inline-block; /* Convierte el enlace en un bloque en línea */
  background-color: #f0f0f0; /* Color de fondo gris claro */
  color: #333; /* Color del texto gris oscuro */
  padding: 8px 16px; /* Espaciado interno (arriba/abajo, izquierda/derecha) */
  text-decoration: none; /* Elimina el subrayado del enlace */
  border: 1px solid #ccc; /* Borde de 1px de color gris */
  border-radius: 4px; /* Bordes redondeados */
  cursor: pointer; /* Cambia el cursor a una mano al pasar por encima */
  transition: background-color 0.3s, color 0.3s; /* Transiciones suaves para el color de fondo y el texto */
  font-size: 14px; /* Tamaño de la fuente */
  font-family: sans-serif; /* Tipo de fuente */
  margin: 5px;
  text-align:center;/*centrar el texto del boton*/
}

/* Estilos al pasar el cursor por encima del botón */
.detalles:hover {
  background-color: #e0e0e0; /* Cambia el color de fondo al pasar el cursor */
  color: #000; /* Cambia el color del texto al pasar el cursor */
}

/* Estilos para el botón cuando está activo (presionado) */
.detalles:active {
  background-color: #d0d0d0; /* Cambia el color de fondo cuando se hace clic */
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2); /* Agrega una sombra interna */
}

/*opcional*/
.detalles:focus{
    outline: none;/*elimina el borde por defecto al tener el foco*/



}


</style>

<!--logo-->
<a  onClick="usuario()"><img src="imagenes/logo.jpeg" style="height:50px;width:50px"></a>


<?php
    
    if ($_SESSION['usuario'] == "") {
        ?>
     ID pedido : <input type="text" id="usuario" onchange="usuario('<?php echo $_POST['categoria'] ?>')" />

    <?php } else {



        echo "<a onClick=\"cambiar_usuario(' $_POST[categoria]')\" style='background:#E6FF71'>ID : " . $str = ucfirst($_SESSION['usuario']) . "</a><input id='usuario' type='hidden' value='$_SESSION[usuario]'/>";





          if ($_SESSION['usuario'] == 'volantuso') {
              
             echo  "<a href=\"admin.php\" style=\"padding-left: 20px;text-align: right\">Admin</a>";          } 
    }
    ?>
       

<a href="sesion/login_volantuso.php"><img src="https://elpollovolantuso.com/imagenes/usuarios.png" style="width:35px;height:35px"></a>
<a href="https://wa.me/593981770519" id="whatsap">
    <img src="imagenes/whatsapp.jpeg" style="width:25px;height:25px">
    </a>




<div id="encabezado" style="display:">
   

    <?php if ($_SESSION["usuario"] != "" && $_SESSION["usuario"] != "volantuso") { ?>
        <a href="index.php"><img src="https://elpollovolantuso.com/imagenes/carta.png" style="width:25px;height:25px"></a>

        <a onClick="historial_compra('$_SESSION[usuario]')"><img src="https://elpollovolantuso.com/imagenes/historial.png"
                style="width:25px;height:25px"></a>

        <a onClick="ver_deuda('<?php echo $_SESSION['usuario'] ?>')"><img src="https://elpollovolantuso.com/imagenes/pago.png"
                style="width:25px;height:25px"></a>


        <a href="mipedido.php"><img src="https://elpollovolantuso.com/imagenes/carrito.png"
                style="width:25px;height:25px"></a>
        <a id="n_articulos">



        </a>


    <?php } ?>
 


    <!--categorias-->

    <?php





    


    if ($_SESSION['usuario'] == "") {
        /*carrito*/
        $comanda_realizada = mysqli_query($conexion, "SELECT * FROM pedidos WHERE usuario='$_SESSION[usuario]' AND estado!=2");


        while ($muestra_comanda = mysqli_fetch_array($comanda_realizada)) {

            $n_pedidos += count($muestra_comanda['producto']);

            if ($muestra_comanda['delivery'] == 'delivery') {

                $n_delivery++;
            }
            ;
        }


        if ($n_pedidos > 0) {



            //echo "<a style='color:red;margin-top:10px;float:rigth' onClick=\"comandas('$_SESSION[usuario]')\"><img src=\"../../imagenes/carrito.png\" style=\"width: 30px;height: 30px\">" . $n_pedidos . "</a>";
            echo "<a style='color:red;margin-top:10px;float:rigth' href='mipedido.php'><img src=\"../../imagenes/carrito.png\" style=\"width: 30px;height: 30px\">" . $n_pedidos . "</a>";

        }

        if ($n_delivery > 0) {

            echo "<a href=\"menu_cocina.php\" style='color:red'><img src=\"imagenes/delivery.png\" style=\"height: 23px;width: 23px\"></a>";
        }





    } ?>
    <a
        href="https://storage.cloud.google.com/archivos_proyectos/photoshop/72808578_30chavo69941376366372_4409039424063537152_o.jpg"></a>

  




    <hr>
</div>
<br>

<div>


    <?php

    error_reporting(0);

    include("conexion.php");

    ?>

    <!--fecha-->
    <?php
    ini_set('date.timezone', 'America/Guayaquil');


    //echo date("F l h:i");
    

    setlocale(LC_ALL, "es_ES");

// Crear una instancia de IntlDateFormatter para español
$formatter = new IntlDateFormatter(
    'es_ES', // Localización en español
    IntlDateFormatter::FULL, // Formato de fecha completa
    IntlDateFormatter::NONE, // Sin hora
    null, // Usa la zona horaria por defecto
    null,
    "EEEE d 'de' MMMM 'del' yyyy" // Formato deseado
);

// Obtener la fecha formateada
$fecha = $formatter->format(new DateTime());

// Obtener hora y día de la semana
$hora = date("G:i");
$finde = date("w");

// Mostrar resultados (solo para prueba)
echo "Fecha: $fecha<br>";
echo "Hora: $hora<br>";
echo "Día de la semana (0=Domingo): $finde<br>";
    ?>



    <?php

    /*acciones de sistema*/
    //insertar producto en carrito
    if ($_POST['producto'] != "") {


        $insertar_pedido = mysqli_query($conexion, "INSERT INTO pedidos (`usuario`,`producto`,`cantidad`,`precio`,`total`,`estado`,`delivery`,`metodo_pago`,`fecha`,`hora`) VALUES ('$_SESSION[usuario]','$_POST[producto]','$_POST[cantidad]','$_POST[precio]','$_POST[total]',0,'default','default','$fecha','$hora')");
    }


    //eliminar_producto de menu
    
    if ($_POST['eliminar_id'] != "") {

        $eliminar_producto_menu = mysqli_query($conexion, "DELETE FROM menu WHERE id='$_POST[eliminar_id]'");

        $nombre_archivo_servidor = $_POST['nombre_archivo_servidor'];

        //unlink('imagenes/'.$nombre_archivo_servidor.'jpeg');
        unlink('imagenes/' . $nombre_archivo_servidor);

    }



    echo $_POST['id_textarea'];
    ?>









    <!---INICIO de sesion usuario--->
    <div>
        <!---Inicio de sesion-->

        <?php



        ////
        if ($_POST['inicio_sesion'] != "") {

            $_SESSION['usuario'] = $_POST['usuario'];


        } 

        ?>




        <?php
        if ($_SESSION['usuario'] != "") {
            include("asi_sistema/info/usuarios/saldo_clientes.php");
        }
        ?>


        <?php
        if (isset($_POST['cambiar_usuario'])) {
            session_destroy();
            echo "Sesión cerrada";
            exit;
        }
        ?>

    </div>


    <!-- Hamburger Menu -->
<div class="hamburger">
  <span class="line"></span>
  <span class="line"></span>
  <span class="line"></span>
</div>
    <br>


    <a id="show_categorias"></a>
    <!---categorias--->
    <div id="contenedor" style="text-align: center;)">


        <div id="categorias">



        

            <?php
            $categoriasMenu[0];
            $muestra_categorias = mysqli_query($conexion, "SELECT DISTINCT categoria FROM menu");

            while ($categoria = mysqli_fetch_array($muestra_categorias)) {

                ?>


            <?php
            if ($_POST['categoria'] == $categoria['categoria']) {

                ?>
            <button onClick="categorias_menu('<?php echo $categoria['categoria'] ?>','<?php echo $_POST['usuario'] ?>')"
                style="color: #A9A9A9;background:#FEF9E7">
                <?php echo $categoria['categoria'] ?>
            </button>
            <?php
            } else {


                ?>
                
            <button onClick="categorias_menu('<?php echo $categoria['categoria'] ?>','<?php echo $_POST['usuario'] ?>')">
                <?php echo $categoria['categoria'] ?>
            </button>

            

            <?php }

            ///muestra el menu eligiendo una categoria aleatoria
        
            $categoria['categoria'];
            $categoriasMenu[] = ("$categoria[categoria]");
            ?>

            <?php
            } ?>

        </div>

        <br>
        <?php

        //recorre array 
        for ($i = 0; $i < count($categoriasMenu); ++$i) {
            //echo "<br>" . $categoriasMenu[$i];
            //echo "<br>" . $categoriasMenu[$i];
            //echo $i;
            $n_aleatorio = $i;
        }
        // $m = implode(" ", $categoriasMenu);
        $menu_aleatorio = rand(0, $n_aleatorio);



        ?>


        <div style="overflow-y: scroll;height: 450px;background:#fff948   ; " id="muestra_menu">



            <?php


            if ($_POST['categoria'] == "") {
                $muestra_menu = mysqli_query($conexion, "SELECT * FROM menu WHERE categoria='$categoriasMenu[$menu_aleatorio]' AND estado='1'");
            } else {


                $muestra_menu = mysqli_query($conexion, "SELECT * FROM menu WHERE categoria='$_POST[categoria]' AND estado!=0 || producto='$_POST[producto]'");
            }
            while ($menu = mysqli_fetch_array($muestra_menu)) {


                ?>
<div style="margin-bottom: 25px;">
            <table>
            <tr><td style="text-align: center;"><h3 style="background: black; color: white; margin: 0; padding: 10px;"><?php echo $menu['producto'] ?></h3></td></tr>
                <tr>

                    <td>

                        <?php if ($_SESSION['usuario'] == 'volantuso') { ?>
                        <a id='<?php echo str_replace(' ', '', $menu['producto'] . "img") ?>'><img
                                src="imagenes/<?php echo $menu['img'] ?>"
                                onClick="detalles('<?php echo $menu['producto'] ?>','<?php echo $menu['id'] ?>','<?php echo $_POST['usuario'] ?>')"></a>

                        <?php } else {
                            ?>

                        <a id='<?php echo str_replace(' ', '', $menu['producto'] . "img") ?>'><img
                                src="imagenes/<?php echo $menu['img'] ?>"></a>


                        <?php } ?>

                        <?php  ///
                            $test += count($menu['producto']);
                            ?>
                    </td>


                </tr>
                <tr>
                        <!--detalles del plato-->
                    <td style="width: 100px;color:#818B97;text-align:center;">
                        <?php //echo $menu['detalles']; ?>

                        <a class="detalles">Descripcion del plato</a>
                    </td>
                </tr>

                <tr>
                    <td class="price-circle">
                        <?php echo "$ " . $menu['precio'] ?>
                    </td>

                </tr>
                <tr>
                    <td style="text-align: center;">
                        <button id='<?php echo str_replace(' ', '', $menu['producto']) ?>' style="color: green"
                            onClick="agregar('<?php echo $menu['producto'] ?>','<?php echo $_SESSION['usuario'] ?>','<?php echo $menu['precio'] ?>','<?php echo $_POST['categoria'] ?>')">Agregar</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <!--dvvvddvdvdvd-->
                            <?php


                            $pedidos = mysqli_query($conexion, "SELECT * FROM pedidos WHERE producto='$menu[producto]' AND usuario='$_SESSION[usuario]' AND estado!='2'");
                            while ($pedidos = mysqli_fetch_array($pedidos)) {


                                if ($menu['producto'] == $pedidos['producto']) {
                                    echo "<a style='color:red'>Agregado</a>";

                                    ?>

                                    <!--condicion para poder quitar producto-->
                                    <?php


                                    ?>

                                    <!--<button style="color: red;margin-left: 50px;" onClick="quitar_producto('<?php //echo $muestra_pedidos['id'] ?>','<?php //echo $_SESSION[usuario] ?>','<?php //echo $_POST[categoria] ?>')">X</button>-->



                                    <?php
                                    $t = str_replace(' ', '', $menu['producto'] . "img");



                                    echo "<script> 
					$('#$t').css('opacity','0.3')
					</script>";

                                    $s = str_replace(' ', '', $menu['producto']);
                                    echo "<script> 
					$('#$s').css('display','none');
					</script>";

                                }
                                ?>






                                <?php


                            }





                            ?>
                        </td>
                    </tr>

                    <?php
                    if ($_SESSION['usuario'] == 'volantuso') {



                        ?>
                        <tr>

                            <td>
                                <button
                                    onClick="eliminar_producto('<?php echo $menu['id'] ?>','<?php echo $menu['seccion'] ?>','<?php echo $menu['img'] ?>')"
                                    style="color: red">x</button>

                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <?php
                        if ($_SESSION['usuario'] == 'volantuso') {
                            ?>
                            <td style="color: blue;">
                                <?php echo "Tiempo de Preparacion <a onClick=\"tiempo_aprox('$menu[id]','$_POST[categoria]','$_POST[usuario]')\">" . $menu['tiempo_aprox'] . "</a> Min" ?>
                            </td>
                        <?php } else { ?>
                            <td style="color: blue;">
                                <?php echo "Tiempo de Preparacion <a>" . $menu['tiempo_aprox'] . "</a> Min" ?>
                            </td>
                        <?php } ?>
                    </tr>
                </table>

</div>



                        <!--descripcion del plato-->
<div style="margin-bottom: 25px;">
    <!-- Agrega un atributo data-* con el ID del plato -->
    <table data-plato-id="<?php echo $menu['id']; ?>">
        <tr>
            <td style="text-align: center;">
                <h3 style="background: black; color: white; margin: 0; padding: 10px;">
                    <?php echo $menu['producto'] ?>
                </h3>
            </td>
        </tr>
        <tr>
            <td>
                <a id='<?php echo str_replace(' ', '', $menu['producto'] . "img") ?>'><img
                        src="imagenes/<?php echo $menu['img'] ?>"></a>
            </td>
        </tr>
        <tr>
            <!--detalles del plato-->
            <td style="width: 100px;color:#818B97;text-align:center;">
                <a class="detalles">Descripcion del plato</a>
            </td>
        </tr>
        <!-- Rest of the table content -->
         <tr>
            <td class="price-circle">
               <?php echo "$ " . $menu['precio'] ?>
           </td>

        </tr>
    </table>
</div>






            <?php } ?>
            <?php include("receta.php"); ?>






        </div>

    </div>

</div>







                        <!--detalles del plato-->




<div id="footer">

    <?php include("asi_sistema/info/footer.php"); ?>


</div>