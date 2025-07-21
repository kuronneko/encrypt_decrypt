// Acordeon
var titulosAcordeon = document.querySelectorAll('.acordeon__titulo');
titulosAcordeon.forEach(function (titulo) {
    titulo && titulo.addEventListener('click', function () {
        var contenido = titulo.nextElementSibling;
        contenido.classList.toggle('acordeonOculto');
        titulo.children[0].nextElementSibling.classList.toggle('rotate180');
    });
});


// document.getElementById("editar__oficina").style.display = "none";
let element = document.getElementById("editar__oficina");
if (element) element.style.display = "none";
function functionEditar() {
    var x = document.getElementById("editar__oficina");
    if (x.style.display === "none") {
        x.style.display = "block";
    } else {
        x.style.display = "none";
    }
}

// document.getElementById("ver__proyectos").style.display = "none";
element = document.getElementById("editar__oficina");
if (element) element.style.display = "none";
function functionVerOT() {
    var x = document.getElementById("ver__proyectos");
    if (x.style.display === "none") {
        x.style.display = "block";
    } else {
        x.style.display = "none";
    }
}

