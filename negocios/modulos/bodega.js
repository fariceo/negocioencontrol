// Recargar módulo
function recargarModulo(){
    fetch('/negocioencontrol/negocios/modulos/bodega.php')
    .then(res => res.text())
    .then(html => {
        document.getElementById('contenido').innerHTML = html;
    });
}

document.addEventListener('DOMContentLoaded', () => {

    const contenido = document.getElementById('contenido');

    // Delegación de eventos
    contenido.addEventListener('click', (e) => {

        // Abrir modal agregar
        if(e.target.id === 'btnAgregar'){
            document.getElementById('modalTitle').textContent = "Agregar Producto";
            document.getElementById('productoId').value = "";
            document.getElementById('nombreProducto').value = "";
            document.getElementById('descripcionProducto').value = "";
            document.getElementById('cantidadProducto').value = "";
            document.getElementById('precioProducto').value = "";
            document.getElementById('modalProducto').style.display = "block";
        }

        // Cerrar modal
        if(e.target.id === 'cerrarModal'){
            document.getElementById('modalProducto').style.display = "none";
        }

        // Editar producto
        if(e.target.classList.contains('editar')){
            const card = e.target.parentElement;
            document.getElementById('modalTitle').textContent = "Editar Producto";
            document.getElementById('productoId').value = e.target.dataset.id;
            document.getElementById('nombreProducto').value = card.querySelector('.producto-nombre').textContent;
            document.getElementById('descripcionProducto').value = card.querySelector('.producto-desc').textContent;
            document.getElementById('cantidadProducto').value = card.querySelector('.producto-stock span').textContent;
            document.getElementById('precioProducto').value = card.querySelector('.producto-precio').textContent.replace('$','');
            document.getElementById('modalProducto').style.display = "block";
        }

        // Eliminar producto
        if(e.target.classList.contains('eliminar')){
            if(!confirm("¿Eliminar este producto?")) return;
            const id = e.target.dataset.id;
            fetch('/negocioencontrol/negocios/modulos/bodega_accion.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id, eliminar:true})
            }).then(res=>res.json()).then(resp=>{
                alert(resp.msg);
                recargarModulo();
            });
        }

        // Guardar producto
        if(e.target.id === 'guardarProducto'){
            const id = document.getElementById('productoId').value;
            const nombre = document.getElementById('nombreProducto').value.trim();
            const descripcion = document.getElementById('descripcionProducto').value.trim();
            const cantidad = parseFloat(document.getElementById('cantidadProducto').value);
            const precio = parseFloat(document.getElementById('precioProducto').value);

            if(!nombre || isNaN(cantidad) || isNaN(precio)){
                alert("⚠️ Completa todos los campos correctamente");
                return;
            }

            fetch('/negocioencontrol/negocios/modulos/bodega_accion.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({id,nombre,descripcion,cantidad,precio})
            }).then(res=>res.json()).then(resp=>{
                alert(resp.msg);
                if(resp.ok){
                    document.getElementById('modalProducto').style.display = "none";
                    recargarModulo();
                }
            });
        }

    });

});
