{{-- Editor de texto enriquecido para los campos .editor-html --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>
<script>
$(function () {
    $('.editor-html').summernote({
        height: 180,
        toolbar: [['estilo', ['bold', 'italic', 'underline', 'clear']], ['parrafo', ['ul', 'ol']], ['insertar', ['link']], ['ver', ['codeview']]],
        // Las imágenes pegadas no se guardan (se suben como bloque o imagen del carrusel)
        callbacks: { onImageUpload: function () { swal('Las imágenes se suben en el campo "Imagen" del bloque.'); } }
    });
});
</script>
