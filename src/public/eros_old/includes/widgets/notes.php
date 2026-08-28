<div class="widget-controls">
    <div class="ctrl-dot ctrl-close" onclick="IxeaWidgets.close()"></div>
    <div class="ctrl-dot ctrl-move" onclick="IxeaWidgets.nextPos()"></div>
</div>

<div id="notes-widget" class="p-3 pt-0 h-100 d-flex flex-column">
    <div id="notes-list" class="overflow-auto mb-2 flex-grow-1" style="max-height: 180px;">
        </div>

    <div class="note-editor border-top border-secondary pt-2">
        <input type="hidden" id="current-note-id" value="">
        <textarea id="note-textarea" class="form-control form-control-sm bg-transparent text-white border-0 p-0" 
                  rows="3" placeholder="Nueva nota..." style="resize: none; font-size: 0.8rem;"></textarea>
        <div class="d-flex justify-content-between mt-2">
            <button class="btn btn-link btn-sm text-secondary p-0" onclick="NotesApp.clear()">Cancelar</button>
            <button class="btn btn-primary btn-sm px-3" onclick="NotesApp.save()">Guardar</button>
        </div>
    </div>
</div>

<script>
    // Inicializar inmediatamente al cargar el widget
    setTimeout(() => NotesApp.load(), 50);
</script>