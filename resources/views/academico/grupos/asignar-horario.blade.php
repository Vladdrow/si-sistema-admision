@extends('layouts.app')

@section('title', "Asignar horario: {$grupo->nombre_grupo}")
@section('subtitle', 'Seleccione una plantilla, luego asigne un docente por materia y un aula por bloque.')

@section('topbar')
    <a href="{{ route('grupos.index') }}" class="button secondary" style="margin-left:auto;">Volver a grupos</a>
@endsection

@section('content')
    <div class="panel">
        <form method="POST" action="{{ route('grupos.asignar-horario.store', $grupo->id_grupo) }}" id="asignar-horario-form">
            @csrf

            <div class="field" style="margin-bottom:16px;">
                <label for="id_plantilla">Plantilla de horario</label>
                <select id="id_plantilla" name="id_plantilla" required>
                    <option value="">Seleccione una plantilla</option>
                    @foreach ($plantillas as $plantilla)
                        <option value="{{ $plantilla->id_plantilla }}"
                            data-detalles="{{ json_encode($plantilla->detalles->map(fn($d) => [
                                'id_detalle' => $d->id_detalle,
                                'dia' => (int) $d->dia,
                                'hora_inicio' => substr((string) $d->hora_inicio, 0, 5),
                                'hora_fin' => substr((string) $d->hora_fin, 0, 5),
                                'id_materia' => $d->id_materia,
                                'materia' => $d->materia?->nombre,
                                'modalidad' => $d->modalidad,
                            ])) }}">
                            {{ $plantilla->nombre }} ({{ $plantilla->turno }} — {{ $plantilla->detalles->count() }} bloques)
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="bloques-container" style="display:none;">
                <div class="section-header">
                    <h3>Asignar docentes por materia</h3>
                    <p style="color:#64748b;font-size:13px;">Cada materia tiene un unico docente para todo el grupo.</p>
                </div>
                <div id="docentes-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px; margin-bottom:20px;"></div>

                <div class="section-header">
                    <h3>Asignar aula por bloque</h3>
                </div>
                <div id="horario-planner" class="week-planner" style="margin-bottom:16px;"></div>

                <button type="submit" class="button primary" id="save-horario">Guardar horario</button>
            </div>
        </form>
    </div>

    <script>
    (function () {
        const plantillaSelect = document.getElementById('id_plantilla');
        const bloquesContainer = document.getElementById('bloques-container');
        const docentesGrid = document.getElementById('docentes-grid');
        const planner = document.getElementById('horario-planner');
        const saveButton = document.getElementById('save-horario');
        const grupoId = {{ $grupo->id_grupo }};
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const dias = ['', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        const docentesList = {!! json_encode($docentes->map(fn($d) => [
            'id' => $d->id_docente,
            'nombre' => $d->persona?->nombre_completo,
            'registro' => $d->persona?->credencial?->registro,
            'materias' => $d->materiasHabilitadas->pluck('id_materia')->values(),
        ])) !!};
        const aulasList = {!! json_encode($aulas->map(fn($a) => ['id' => $a->id_aula, 'nombre' => $a->nombre, 'capacidad' => $a->capacidad])) !!};
        const materiaColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#e0e7ff', '#ccfbf1', '#f5f5f4', '#ffedd5'];

        const docentesSelect = (name, selectedId, materiaId) => {
            const docentesDisponibles = docentesList.filter(d => (d.materias || []).includes(Number(materiaId)));

            return '<select name="' + name + '" class="docente-select" data-validate="docente" required style="width:100%;">' +
                '<option value="">Seleccionar docente</option>' +
                docentesDisponibles.map(d => '<option value="' + d.id + '">' + d.nombre + ' — ' + (d.registro || 'N/A') + '</option>').join('') +
            '</select><span class="validate-feedback" style="display:block;font-size:11px;margin-top:4px;">' +
                (docentesDisponibles.length ? '' : 'No hay docentes habilitados para esta materia') +
            '</span>';
        };

        const aulasSelect = (name) =>
            '<select name="' + name + '" class="aula-select" data-validate="aula" required style="width:100%;">' +
                '<option value="">Seleccionar aula</option>' +
                aulasList.map(a => '<option value="' + a.id + '">' + a.nombre + ' (cap. ' + a.capacidad + ')</option>').join('') +
            '</select><span class="validate-feedback" style="display:block;font-size:11px;margin-top:4px;"></span>';

        const validar = async (tipo, id) => {
            if (!id || !plantillaSelect.value) return;
            const url = '/grupos/' + grupoId + '/validar-asignacion?tipo=' + tipo + '&id=' + id + '&id_plantilla=' + plantillaSelect.value;
            const resp = await fetch(url, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            return resp.ok ? resp.json() : null;
        };

        const mostrarFeedback = (select, data) => {
            const feedback = select.parentElement.querySelector('.validate-feedback');
            if (!feedback) return;
            if (!data || data.disponible) {
                feedback.textContent = 'Disponible';
                feedback.style.color = '#16a34a';
                select.style.borderColor = '#16a34a';
            } else {
                const conflictos = data.conflictos.map(c => c.grupo + ' (' + dias[c.dia] + ' ' + c.hora + ')').join(', ');
                feedback.textContent = 'Ocupado: ' + conflictos;
                feedback.style.color = '#dc2626';
                select.style.borderColor = '#dc2626';
            }
        };

        document.addEventListener('change', async function (e) {
            const select = e.target.closest('[data-validate]');
            if (!select || !select.value) return;
            const tipo = select.dataset.validate;
            const data = await validar(tipo, select.value);
            mostrarFeedback(select, data);
        });

        plantillaSelect.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            if (!option?.value) {
                bloquesContainer.style.display = 'none';
                return;
            }

            const detalles = JSON.parse(option.dataset.detalles || '[]');

            const materiasUnicas = [];
            const seenMaterias = new Set();
            detalles.forEach(d => {
                if (d.id_materia && !seenMaterias.has(d.id_materia)) {
                    seenMaterias.add(d.id_materia);
                    materiasUnicas.push(d);
                }
            });

            docentesGrid.innerHTML = materiasUnicas.map(d =>
                '<div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px;">' +
                    '<label style="font-size:12px; color:#64748b; display:block; margin-bottom:4px;">' + (d.materia || 'Materia sin nombre') + '</label>' +
                    docentesSelect('docentes[' + d.id_materia + ']', '', d.id_materia) +
                '</div>'
            ).join('') || '<p class="dashboard-empty">No hay materias definidas en esta plantilla.</p>';

            planner.innerHTML = '';
            const columns = {};
            [1, 2, 3, 4, 5, 6, 7].forEach(day => { columns[day] = document.createElement('div'); columns[day].className = 'day-column'; columns[day].innerHTML = '<h4>' + (dias[day] || day) + '</h4>'; });

            const dayDetails = {};
            detalles.forEach(d => {
                if (!dayDetails[d.dia]) dayDetails[d.dia] = [];
                dayDetails[d.dia].push(d);
            });

            Object.entries(dayDetails).forEach(([day, blocks]) => {
                blocks.sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));
                blocks.forEach(d => {
                    const color = d.id_materia ? materiaColors[Number(d.id_materia) % materiaColors.length] : '';
                    const block = document.createElement('div');
                    block.className = 'schedule-block';
                    if (color) {
                        block.style.borderLeft = '4px solid ' + color;
                        block.style.background = color;
                    }
                    block.innerHTML =
                        '<strong>' + d.hora_inicio + ' - ' + d.hora_fin + '</strong>' +
                        '<span>' + (d.materia || 'Sin materia') + ' / ' + d.modalidad + '</span>' +
                        '<div style="margin-top:8px;">' +
                            '<input type="hidden" name="asignaciones[' + detalles.indexOf(d) + '][id_detalle]" value="' + d.id_detalle + '">' +
                            '<label style="font-size:11px; color:#64748b;">Aula</label>' +
                            aulasSelect('asignaciones[' + detalles.indexOf(d) + '][id_aula]') +
                        '</div>';
                    columns[day].appendChild(block);
                });
            });

            [1, 2, 3, 4, 5, 6, 7].forEach(day => {
                if (columns[day].children.length <= 1) {
                    const empty = document.createElement('span');
                    empty.className = 'muted';
                    empty.textContent = 'Sin bloques';
                    columns[day].appendChild(empty);
                }
                planner.appendChild(columns[day]);
            });

            bloquesContainer.style.display = 'block';
        });
    })();
    </script>
@endsection
