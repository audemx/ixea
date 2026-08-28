<div class="widget-controls mt-0 pt-0">
    <div class="ctrl-dot ctrl-close" onclick="IxeaWidgets.close()"></div>
    <div class="ctrl-dot ctrl-move" onclick="IxeaWidgets.nextPos()"></div>
</div>

<div id="calculator-widget" class="p-0 d-flex flex-column gap-2">
    <div class="calc-display bg-black bg-opacity-40 px-2 rounded-3 text-end mb-2">
        <div id="calc-prev" class="text-secondary small" style="font-size: 0.5rem; min-height: 15px;"></div>
        <div id="calc-main" class="fs-6 fw-bold text-white lh-1">0</div>
    </div>

    <div class="calc-grid d-grid gap-1" style="grid-template-columns: repeat(4, 1fr);">
        <button class="btn btn-dark btn-sm" onclick="calc.clear()">C</button>
        <button class="btn btn-dark btn-sm" onclick="calc.backspace()"><i class="bi bi-backspace"></i></button>
        <button class="btn btn-dark btn-sm" onclick="calc.op('%')">%</button>
        <button class="btn btn-dark btn-sm" onclick="calc.op('/')">÷</button>
        
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('7')">7</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('8')">8</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('9')">9</button>
        <button class="btn btn-dark btn-sm" onclick="calc.op('*')">×</button>
        
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('4')">4</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('5')">5</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('6')">6</button>
        <button class="btn btn-dark btn-sm" onclick="calc.op('-')">-</button>
        
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('1')">1</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('2')">2</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('3')">3</button>
        <button class="btn btn-dark btn-sm" onclick="calc.op('+')">+</button>
        
        <button class="btn btn-secondary bg-opacity-10 btn-sm" style="grid-column: span 2;" onclick="calc.num('0')">0</button>
        <button class="btn btn-secondary bg-opacity-10 btn-sm" onclick="calc.num('.')">.</button>
        <button class="btn btn-primary btn-sm shadow" onclick="calc.equal()">=</button>
    </div>
</div>