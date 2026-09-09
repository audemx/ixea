/**
 * IXEA EROS - Widgets:
 * Calculadora, NotesApp, CalendarApp
 */


/** -- Calculadora -- **/
const calc = {
    display: '0',
    prev: '',
    operator: null,
    newNumber: true,

    update: function() {
        // Limitar a 10 caracteres para evitar desbordamiento
        let out = this.display;
        if (out.length > 10) {
            out = parseFloat(out).toPrecision(7); // Notación científica si es muy grande
        }
        document.getElementById('calc-main').innerText = out;
        document.getElementById('calc-prev').innerText = this.prev;
    },

    num: function(n) {
        if (this.display.length >= 10 && !this.newNumber) return; // Límite de entrada
        if (this.newNumber) {
            this.display = n;
            this.newNumber = false;
        } else {
            this.display += n;
        }
        this.update();
    },

    op: function(o) {
        if (this.operator && !this.newNumber) this.equal();
        this.operator = o;
        this.prev = this.display + ' ' + o;
        this.newNumber = true;
        this.update();
    },

    equal: function() {
        let res;
        const a = parseFloat(this.prev);
        const b = parseFloat(this.display);
        
        if (this.operator === '/' && b === 0) {
            this.display = "Error Div0";
            this.newNumber = true;
            return this.update();
        }

        switch(this.operator) {
            case '+': res = a + b; break;
            case '-': res = a - b; break;
            case '*': res = a * b; break;
            case '/': res = a / b; break;
            case '%': res = (a * b) / 100; break;
            default: return;
        }

        if (isNaN(res) || !isFinite(res)) {
            this.display = "Error Num";
        } else {
            this.display = res.toString();
        }
        
        this.prev = '';
        this.operator = null;
        this.newNumber = true;
        this.update();
    }
};


/** -- Notas -- **/
const NotesApp = {
    load: function() {
        const list = document.getElementById('notes-list');
        if(!list) return;

        fetch('/includes/widgets/notes-processor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=list'
        })
        .then(res => res.json())
        .then(data => {
            list.innerHTML = data.map(n => `
                <div class="note-item mb-2 p-2 rounded bg-white bg-opacity-5 position-relative border border-secondary border-opacity-25">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-secondary" style="font-size: 0.6rem;">${n.fecha}</span>
                        <i class="bi bi-x-lg text-danger cursor-pointer" 
                           style="font-size: 0.7rem;" onclick="NotesApp.delete(${n.note_id})"></i>
                    </div>
                    <div class="small text-white cursor-pointer pe-3" 
                         onclick="NotesApp.edit(${n.note_id}, \`${n.note.replace(/"/g, '&quot;')}\`)">
                         ${n.note}
                    </div>
                </div>
            `).join('') || '<div class="text-center text-secondary small mt-4">No hay notas guardadas</div>';
        });
    },

    save: function() {
        const textarea = document.getElementById('note-textarea');
        const note_id = document.getElementById('current-note-id').value;
        const note = textarea.value.trim();

        // Si es una nota nueva (sin ID) y está vacía, no enviamos nada
        if (!note && !note_id) return;

        fetch('/includes/widgets/notes-processor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=save&note=${encodeURIComponent(note)}&note_id=${note_id}`
        }).then(res => res.json())
        .then(data => {
            this.clear();
            this.load(); // Recargamos la lista para ver el cambio (o la eliminación)
        });
    },

    edit: function(id, text) {
        const textarea = document.getElementById('note-textarea');
        document.getElementById('current-note-id').value = id;
        textarea.value = text;
        textarea.focus();
        
        // Efecto visual de selección en el editor
        textarea.style.borderBottom = "1px solid var(--bs-primary)";
    },

    clear: function() {
        document.getElementById('current-note-id').value = '';
        const textarea = document.getElementById('note-textarea');
        textarea.value = '';
        textarea.style.borderBottom = "none";
    }
};


/** -- Calendario -- **/
const CalendarApp = {
    date: new Date(),

    init: function() {
        this.render();
    },

    prev: function() {
        this.date.setMonth(this.date.getMonth() - 1);
        this.render();
    },

    next: function() {
        this.date.setMonth(this.date.getMonth() + 1);
        this.render();
    },

    render: function() {
        const monthEl = document.getElementById('cal-month-year');
        const daysEl = document.getElementById('cal-days');
        if(!daysEl) return;

        const year = this.date.getFullYear();
        const month = this.date.getMonth();
        
        // Título del mes
        const months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        monthEl.innerText = `${months[month]} ${year}`;

        // Cálculo de días
        const firstDay = new Date(year, month, 1).getDay();
        const lastDay = new Date(year, month + 1, 0).getDate();
        const today = new Date();

        let html = '';
        
        // Espacios para días del mes anterior
        for(let i = 0; i < firstDay; i++) {
            html += `<div class="cal-day other-month"></div>`;
        }

        // Días del mes actual
        for(let i = 1; i <= lastDay; i++) {
            const isToday = today.getDate() === i && today.getMonth() === month && today.getFullYear() === year ? 'today' : '';
            html += `<div class="cal-day ${isToday}">${i}</div>`;
        }

        daysEl.innerHTML = html;
    }
};