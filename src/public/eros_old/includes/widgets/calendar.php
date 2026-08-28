<div class="widget-controls">
    <div class="ctrl-dot ctrl-close" onclick="IxeaWidgets.close()"></div>
    <div class="ctrl-dot ctrl-move" onclick="IxeaWidgets.nextPos()"></div>
</div>

<div id="calendar-widget" class="p-3 pt-0 h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 id="cal-month-year" class="m-0 text-white small fw-bold text-uppercase"></h6>
        <div class="btn-group border border-secondary rounded-pill overflow-hidden" style="scale: 0.8;">
            <button class="btn btn-dark btn-sm p-1 px-2" onclick="CalendarApp.prev()"><i class="bi bi-chevron-left"></i></button>
            <button class="btn btn-dark btn-sm p-1 px-2" onclick="CalendarApp.next()"><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
    
    <div class="calendar-grid d-grid text-center text-secondary small" style="grid-template-columns: repeat(7, 1fr); font-size: 0.65rem;">
        <div>DO</div><div>LU</div><div>MA</div><div>MI</div><div>JU</div><div>VI</div><div>SÁ</div>
    </div>
    <hr class="border-secondary my-2 opacity-25">
    <div id="cal-days" class="calendar-grid d-grid text-center" style="grid-template-columns: repeat(7, 1fr); row-gap: 8px;">
        </div>
</div>

<script>
    setTimeout(() => CalendarApp.init(), 50);
</script>