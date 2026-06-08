const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const showMessage = (target, message, type = 'ok') => {
    if (!target) {
        return;
    }

    target.textContent = message;
    target.className = `inline-message ${type} is-open`;
};

const clearMessage = (target) => {
    if (!target) {
        return;
    }

    target.textContent = '';
    target.className = 'inline-message';
};

const readErrors = async (response) => {
    const payload = await response.json().catch(() => ({}));

    if (payload.errors) {
        return Object.values(payload.errors).flat().join(' ');
    }

    return payload.message || 'No se pudo completar la accion.';
};

const setupSidebar = () => {
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-sidebar-close]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    const openSidebar = () => {
        document.body.classList.add('sidebar-open');
        openButton?.setAttribute('aria-expanded', 'true');
    };

    const closeSidebar = () => {
        document.body.classList.remove('sidebar-open');
        openButton?.setAttribute('aria-expanded', 'false');
    };

    openButton?.addEventListener('click', openSidebar);
    backdrop?.addEventListener('click', closeSidebar);
    closeButtons.forEach((button) => button.addEventListener('click', closeSidebar));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
};

const setupAjaxFilters = () => {
    document.querySelectorAll('[data-ajax-filter]').forEach((form) => {
        const results = form.parentElement?.querySelector('[data-results]');
        const fields = form.querySelectorAll('[data-filter-field]');
        const clearButton = form.querySelector('[data-clear-filters]');
        let timer = null;

        if (!results) {
            return;
        }

        const filterUrl = (base = form.action) => {
            const url = new URL(base, window.location.origin);
            fields.forEach((field) => {
                const value = field.value?.trim?.() ?? field.value;

                if (value !== '') {
                    url.searchParams.set(field.name, value);
                } else {
                    url.searchParams.delete(field.name);
                }
            });

            return url;
        };

        const loadResults = async (url = filterUrl()) => {
            results.classList.add('is-loading');

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                results.innerHTML = await response.text();
            } finally {
                results.classList.remove('is-loading');
            }
        };

        const scheduleLoad = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => loadResults(), 250);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            loadResults();
        });

        clearButton?.addEventListener('click', (event) => {
            event.preventDefault();
            fields.forEach((field) => {
                field.value = '';
            });
            loadResults(new URL(clearButton.href));
        });

        fields.forEach((field) => {
            field.addEventListener(field.tagName === 'SELECT' ? 'change' : 'input', scheduleLoad);
        });

        results.addEventListener('click', (event) => {
            const paginationLink = event.target.closest('.pagination a');

            if (paginationLink) {
                event.preventDefault();
                loadResults(new URL(paginationLink.href));
            }
        });

        results.reloadResults = () => loadResults();
    });
};

const setupCredentials = () => {
    const modal = document.getElementById('credential-workspace');
    const form = document.getElementById('credential-form');

    if (!modal || !form) {
        return;
    }

    const pageMessage = document.getElementById('page-message');
    const modalMessage = document.getElementById('modal-message');
    const saveButton = document.getElementById('save-credential');
    const resultsContainer = document.querySelector('[data-results]');
    let activeRow = null;

    const fields = {
        id: document.getElementById('credential-id'),
        persona: document.getElementById('credential-persona'),
        personField: document.getElementById('credential-person-field'),
        registro: document.getElementById('credential-registro'),
        ci: document.getElementById('credential-ci'),
        nombre: document.getElementById('credential-nombre'),
        correo: document.getElementById('credential-correo'),
        rol: document.getElementById('credential-rol'),
        estado: document.getElementById('credential-estado'),
        password: document.getElementById('credential-password'),
        passwordConfirmation: document.getElementById('credential-password-confirmation'),
        title: document.getElementById('credential-modal-title'),
        subtitle: document.getElementById('credential-modal-subtitle'),
    };

    const resetCredentialForm = () => {
        activeRow = null;
        clearMessage(modalMessage);
        form.reset();
        fields.id.value = '';
        fields.registro.disabled = true;
        fields.registro.placeholder = 'Se genera automaticamente';
        fields.rol.disabled = true;
        fields.persona.disabled = false;
        fields.persona.required = true;
        fields.personField.hidden = false;
        fields.password.required = false;
        fields.passwordConfirmation.required = false;
        fields.password.placeholder = 'Dejar vacio para usar el CI';
        fields.title.textContent = 'Registrar credencial';
        fields.subtitle.textContent = 'Seleccione una persona sin credencial o modifique una existente desde la tabla.';
        fields.ci.value = '';
        fields.nombre.value = '';
        saveButton.textContent = 'Guardar credencial';
    };

    const openModal = (row) => {
        activeRow = row;
        clearMessage(modalMessage);
        fields.id.value = row.dataset.id;
        fields.persona.value = '';
        fields.persona.disabled = true;
        fields.persona.required = false;
        fields.personField.hidden = true;
        fields.registro.value = row.dataset.registro || '';
        fields.registro.disabled = true;
        fields.ci.value = row.dataset.ci || '';
        fields.nombre.value = row.dataset.nombre || '';
        fields.correo.value = row.dataset.correo || '';
        fields.rol.value = row.dataset.rol || 'Postulante';
        fields.rol.disabled = true;
        fields.estado.value = row.dataset.estado || '1';
        fields.password.value = '';
        fields.passwordConfirmation.value = '';
        fields.password.required = false;
        fields.passwordConfirmation.required = false;
        fields.password.placeholder = 'Dejar vacio para no cambiar';
        fields.title.textContent = 'Modificar credencial';
        fields.subtitle.textContent = `${row.dataset.registro} - ${row.dataset.nombre}`;
        saveButton.textContent = 'Guardar cambios';
        modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
        fields.correo.focus();
    };

    const closeModal = () => {
        resetCredentialForm();
        fields.persona.focus();
    };

    const updateRow = (row, data) => {
        row.dataset.rol = data.rol;
        row.dataset.estado = data.estado ? '1' : '0';
        row.dataset.correo = data.persona.correo || '';
        row.dataset.nombre = data.persona.nombre || 'Sin persona';
        row.dataset.ci = data.persona.ci || '';

        row.querySelector('[data-cell="nombre"]').textContent = data.persona.nombre || 'Sin persona';
        row.querySelector('[data-cell="ci"]').textContent = data.persona.ci || '';
        row.querySelector('[data-cell="correo"]').textContent = data.persona.correo || '';
        row.querySelector('[data-cell="rol"]').textContent = data.rol;
        row.querySelector('[data-cell="ultimo_acceso"]').textContent = data.ultimo_acceso;

        const badge = row.querySelector('[data-cell="estado"]');
        badge.textContent = data.estado_texto;
        badge.className = `badge ${data.estado ? 'ok' : 'off'}`;
    };

    const reloadCredentials = async () => {
        await resultsContainer?.reloadResults?.();
    };

    const changeCredentialState = async (button, action) => {
        const row = button.closest('.credential-row');
        const isRestore = action === 'restore';
        const message = isRestore
            ? `Restaurar la credencial ${row.dataset.registro}?`
            : `Eliminar la credencial ${row.dataset.registro}?`;

        if (!confirm(message)) {
            return;
        }

        clearMessage(pageMessage);
        button.disabled = true;

        try {
            const response = await fetch(isRestore ? row.dataset.restoreUrl : row.dataset.deleteUrl, {
                method: isRestore ? 'PATCH' : 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                return;
            }

            const payload = await response.json();
            await reloadCredentials();
            showMessage(pageMessage, `${payload.message || (isRestore ? 'Credencial restaurada.' : 'Credencial desactivada.')} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
        } finally {
            button.disabled = false;
        }
    };

    resultsContainer?.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-action]');

        if (actionButton) {
            const action = actionButton.dataset.action;

            if (action === 'edit') {
                openModal(actionButton.closest('.credential-row'));
                return;
            }

            if (action === 'delete' || action === 'restore') {
                changeCredentialState(actionButton, action);
            }
        }
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    fields.persona.addEventListener('change', () => {
        const option = fields.persona.selectedOptions[0];

        if (!option?.value) {
            fields.registro.value = '';
            fields.ci.value = '';
            fields.nombre.value = '';
            fields.correo.value = '';
            fields.rol.value = 'Postulante';
            fields.rol.disabled = true;
            fields.password.value = '';
            fields.passwordConfirmation.value = '';
            return;
        }

        fields.registro.value = '';
        fields.registro.placeholder = 'Se genera automaticamente';
        fields.ci.value = option.dataset.ci || '';
        fields.nombre.value = option.dataset.nombre || '';
        fields.correo.value = option.dataset.correo || '';
        fields.rol.value = option.dataset.rol || 'Postulante';
        fields.rol.disabled = true;
        fields.password.value = option.dataset.ci || '';
        fields.passwordConfirmation.value = option.dataset.ci || '';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearMessage(modalMessage);
        saveButton.disabled = true;

        const payload = {
            id_persona: fields.persona.value,
            registro: fields.registro.value,
            rol: fields.rol.value,
            estado: fields.estado.value,
            correo: fields.correo.value,
            nueva_contrasena: fields.password.value,
            nueva_contrasena_confirmation: fields.passwordConfirmation.value,
        };

        try {
            const wasCreating = !activeRow;
            const createdPersonId = fields.persona.value;
            const response = await fetch(activeRow?.dataset.updateUrl || form.dataset.storeUrl, {
                method: activeRow ? 'PATCH' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                showMessage(modalMessage, await readErrors(response), 'error');
                showMessage(pageMessage, 'Ha errado el proceso.', 'error');
                return;
            }

            const result = await response.json();
            if (activeRow) {
                updateRow(activeRow, result.credencial);
            }
            if (wasCreating && createdPersonId) {
                fields.persona.querySelector(`option[value="${CSS.escape(createdPersonId)}"]`)?.remove();
            }
            closeModal();
            await reloadCredentials();
            showMessage(pageMessage, `${result.message || 'Credencial guardada.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(modalMessage, 'No se pudo conectar con el servidor.', 'error');
            showMessage(pageMessage, 'Ha errado el proceso.', 'error');
        } finally {
            saveButton.disabled = false;
        }
    });

    resetCredentialForm();
};

const setupTeachers = () => {
    const modal = document.getElementById('teacher-workspace');
    const form = document.getElementById('teacher-form');

    if (!modal || !form) {
        return;
    }

    const resultsContainer = document.querySelector('[data-results]');
    const pageMessage = document.getElementById('page-message');
    const modalMessage = document.getElementById('teacher-modal-message');
    const saveButton = document.getElementById('save-teacher');
    const title = document.getElementById('teacher-modal-title');
    const subtitle = document.getElementById('teacher-modal-subtitle');
    let activeRow = null;

    const fields = {
        id: document.getElementById('teacher-id'),
        ci: document.getElementById('teacher-ci'),
        nombres: document.getElementById('teacher-nombres'),
        apellidoPaterno: document.getElementById('teacher-apellido-paterno'),
        apellidoMaterno: document.getElementById('teacher-apellido-materno'),
        fechaNacimiento: document.getElementById('teacher-fecha-nacimiento'),
        sexo: document.getElementById('teacher-sexo'),
        direccion: document.getElementById('teacher-direccion'),
        telefono: document.getElementById('teacher-telefono'),
        correo: document.getElementById('teacher-correo'),
        tituloProfesional: document.getElementById('teacher-titulo-profesional'),
        codigoRda: document.getElementById('teacher-codigo-rda'),
        certificacionInstitucion: document.getElementById('teacher-certificacion-institucion'),
        certificacionNivel: document.getElementById('teacher-certificacion-nivel'),
        tieneMaestria: document.getElementById('teacher-tiene-maestria'),
        tieneDiplomado: document.getElementById('teacher-tiene-diplomado'),
    };
    const subjectFields = [...form.querySelectorAll('[data-teacher-subject]')];

    const setValue = (field, value = '') => {
        field.value = value || '';
    };

    const setSubjects = (value = '') => {
        const selected = new Set(String(value || '').split(',').filter(Boolean));
        subjectFields.forEach((field) => {
            field.checked = selected.has(field.value);
        });
    };

    const openModal = (row = null) => {
        activeRow = row;
        clearMessage(modalMessage);
        form.reset();

        if (row) {
            title.textContent = 'Modificar docente';
            subtitle.textContent = `${row.dataset.nombres || ''} ${row.dataset.apellidoPaterno || ''}`.trim();
            setValue(fields.id, row.dataset.id);
            setValue(fields.ci, row.dataset.ci);
            setValue(fields.nombres, row.dataset.nombres);
            setValue(fields.apellidoPaterno, row.dataset.apellidoPaterno);
            setValue(fields.apellidoMaterno, row.dataset.apellidoMaterno);
            setValue(fields.fechaNacimiento, row.dataset.fechaNacimiento);
            setValue(fields.sexo, row.dataset.sexo || 'M');
            setValue(fields.direccion, row.dataset.direccion);
            setValue(fields.telefono, row.dataset.telefono);
            setValue(fields.correo, row.dataset.correo);
            setValue(fields.tituloProfesional, row.dataset.tituloProfesional);
            setValue(fields.codigoRda, row.dataset.codigoRda);
            setValue(fields.certificacionInstitucion, row.dataset.certificacionInstitucion);
            setValue(fields.certificacionNivel, row.dataset.certificacionNivel);
            setSubjects(row.dataset.materiasHabilitadas);
            fields.tieneMaestria.checked = row.dataset.tieneMaestria === '1';
            fields.tieneDiplomado.checked = row.dataset.tieneDiplomado === '1';
        } else {
            title.textContent = 'Registrar docente';
            subtitle.textContent = '';
            setValue(fields.id, '');
            setValue(fields.sexo, 'M');
            setSubjects();
        }

        modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
        fields.ci.focus();
    };

    const closeModal = () => {
        activeRow = null;
        clearMessage(modalMessage);
        form.reset();
        title.textContent = 'Registrar docente';
        subtitle.textContent = '';
        setValue(fields.id, '');
        setValue(fields.sexo, 'M');
        setSubjects();
        fields.ci.focus();
    };

    const formPayload = () => ({
        ci: fields.ci.value,
        nombres: fields.nombres.value,
        apellido_paterno: fields.apellidoPaterno.value,
        apellido_materno: fields.apellidoMaterno.value,
        fecha_nacimiento: fields.fechaNacimiento.value,
        sexo: fields.sexo.value,
        direccion: fields.direccion.value,
        telefono: fields.telefono.value,
        correo: fields.correo.value,
        titulo_profesional: fields.tituloProfesional.value,
        codigo_rda: fields.codigoRda.value,
        materias_habilitadas: subjectFields.filter((field) => field.checked).map((field) => field.value),
        certificacion_institucion: fields.certificacionInstitucion.value,
        certificacion_nivel: fields.certificacionNivel.value,
        tiene_maestria: fields.tieneMaestria.checked ? '1' : '0',
        tiene_diplomado: fields.tieneDiplomado.checked ? '1' : '0',
    });

    document.querySelectorAll('[data-teacher-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    resultsContainer?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-teacher-action]');

        if (!button) {
            return;
        }

        const row = button.closest('.teacher-row');

        if (button.dataset.teacherAction === 'edit') {
            openModal(row);
            return;
        }

        if (button.dataset.teacherAction === 'restore') {
            clearMessage(pageMessage);
            button.disabled = true;

            try {
                const response = await fetch(row.dataset.restoreUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) {
                    showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                    return;
                }

                const payload = await response.json();
                await resultsContainer.reloadResults?.();
                showMessage(pageMessage, `${payload.message || 'Docente restaurado.'} Se ha completado el proceso.`);
            } catch (error) {
                showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
            } finally {
                button.disabled = false;
            }

            return;
        }

        if (!confirm(`Desactivar al docente ${row.dataset.nombres || row.dataset.ci}?`)) {
            return;
        }

        clearMessage(pageMessage);
        button.disabled = true;

        try {
            const response = await fetch(row.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                return;
            }

            const payload = await response.json();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${payload.message || 'Docente desactivado.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
        } finally {
            button.disabled = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearMessage(modalMessage);
        saveButton.disabled = true;

        try {
            const response = await fetch(activeRow?.dataset.updateUrl || form.dataset.storeUrl, {
                method: activeRow ? 'PATCH' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(formPayload()),
            });

            if (!response.ok) {
                showMessage(modalMessage, await readErrors(response), 'error');
                showMessage(pageMessage, 'Ha errado el proceso.', 'error');
                return;
            }

            const payload = await response.json();
            closeModal();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${payload.message || 'Docente guardado.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(modalMessage, 'No se pudo conectar con el servidor.', 'error');
            showMessage(pageMessage, 'Ha errado el proceso.', 'error');
        } finally {
            saveButton.disabled = false;
        }
    });

    closeModal();
};

const setupPersonCrud = () => {
    const modal = document.querySelector('[data-person-modal]');
    const form = document.getElementById('person-form');

    if (!modal || !form) {
        return;
    }

    const resultsContainer = document.querySelector('[data-results]');
    const pageMessage = document.getElementById('page-message');
    const modalMessage = document.getElementById('person-modal-message');
    const saveButton = document.getElementById('save-person');
    const title = document.getElementById('person-modal-title');
    const subtitle = document.getElementById('person-modal-subtitle');
    const kind = modal.dataset.kind;
    const labels = {
        staff: ['personal', 'Registrar personal', 'Modificar personal'],
        applicant: ['postulante', 'Modificar postulante', 'Modificar postulante'],
    }[kind] || ['registro', 'Registrar', 'Modificar'];
    let activeRow = null;

    const fields = Object.fromEntries([...form.querySelectorAll('[data-person-field]')].map((field) => [field.dataset.personField, field]));
    const formControls = [...form.querySelectorAll('input, select, textarea')].filter((field) => field.type !== 'hidden');
    const setApplicantFormEnabled = (enabled) => {
        if (kind !== 'applicant') {
            return;
        }

        modal.classList.toggle('is-disabled', !enabled);
        formControls.forEach((field) => {
            field.disabled = !enabled;
        });
        saveButton.disabled = !enabled;
    };

    const setValue = (name, value = '') => {
        if (fields[name]) {
            fields[name].value = value || '';
        }
    };

    const fillCommon = (row) => {
        setValue('id', row?.dataset.id);
        setValue('ci', row?.dataset.ci);
        setValue('nombres', row?.dataset.nombres);
        setValue('apellidoPaterno', row?.dataset.apellidoPaterno);
        setValue('apellidoMaterno', row?.dataset.apellidoMaterno);
        setValue('fechaNacimiento', row?.dataset.fechaNacimiento);
        setValue('sexo', row?.dataset.sexo || 'M');
        setValue('direccion', row?.dataset.direccion);
        setValue('telefono', row?.dataset.telefono);
        setValue('correo', row?.dataset.correo);
    };

    const openModal = (row = null) => {
        activeRow = row;
        clearMessage(modalMessage);
        setApplicantFormEnabled(true);
        form.reset();
        title.textContent = row ? labels[2] : labels[1];
        subtitle.textContent = row ? `${row.dataset.nombres || ''} ${row.dataset.apellidoPaterno || ''}`.trim() : '';
        fillCommon(row);

        if (kind === 'staff') {
            setValue('cargo', row?.dataset.cargo);
        }

        if (kind === 'applicant') {
            setValue('colegioProcedencia', row?.dataset.colegioProcedencia);
            setValue('ciudad', row?.dataset.ciudad);
            setValue('estadoAdmision', row?.dataset.estadoAdmision || 'Pendiente');
            setValue('codigoLibreta', row?.dataset.codigoLibreta);
            setValue('codigoTitulo', row?.dataset.codigoTitulo);
            setValue('idCarreraPrimeraOpc', row?.dataset.idCarreraPrimeraOpc);
            setValue('idCarreraSegundaOpc', row?.dataset.idCarreraSegundaOpc);
            setValue('idCarreraAdmitido', row?.dataset.idCarreraAdmitido);
        }

        modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
        fields.ci?.focus();
    };

    const closeModal = () => {
        activeRow = null;
        clearMessage(modalMessage);
        form.reset();
        title.textContent = labels[1];
        subtitle.textContent = '';
        if (kind === 'applicant') {
            subtitle.textContent = 'Seleccione un postulante de la tabla para editarlo.';
        }
        setValue('id', '');
        setValue('sexo', 'M');
        if (kind === 'applicant') {
            setValue('estadoAdmision', 'Pendiente');
            setApplicantFormEnabled(false);
        }
        if (kind !== 'applicant') {
            fields.ci?.focus();
        }
    };

    const payload = () => Object.fromEntries([...form.elements]
        .filter((field) => field.name)
        .map((field) => [field.name, field.value]));

    document.querySelectorAll('[data-person-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    resultsContainer?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-person-action]');

        if (!button) {
            return;
        }

        const row = button.closest('tr');

        if (button.dataset.personAction === 'edit') {
            openModal(row);
            return;
        }

        if (button.dataset.personAction === 'restore') {
            clearMessage(pageMessage);
            button.disabled = true;

            try {
                const response = await fetch(row.dataset.restoreUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) {
                    showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                    return;
                }

                const result = await response.json();
                await resultsContainer.reloadResults?.();
                showMessage(pageMessage, `${result.message || 'Registro restaurado.'} Se ha completado el proceso.`);
            } catch (error) {
                showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
            } finally {
                button.disabled = false;
            }

            return;
        }

        const actionVerb = kind === 'staff' ? 'Desactivar' : 'Eliminar';

        if (!confirm(`${actionVerb} ${labels[0]} ${row.dataset.nombres || row.dataset.ci}?`)) {
            return;
        }

        clearMessage(pageMessage);
        button.disabled = true;

        try {
            const response = await fetch(row.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                return;
            }

            const result = await response.json();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${result.message || 'Registro eliminado.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
        } finally {
            button.disabled = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearMessage(modalMessage);

        if (kind === 'applicant' && !activeRow) {
            showMessage(modalMessage, 'Seleccione un postulante de la tabla para modificarlo. El registro lo realiza el postulante.', 'error');
            return;
        }

        saveButton.disabled = true;

        try {
            const response = await fetch(activeRow?.dataset.updateUrl || form.dataset.storeUrl, {
                method: activeRow ? 'PATCH' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload()),
            });

            if (!response.ok) {
                showMessage(modalMessage, await readErrors(response), 'error');
                showMessage(pageMessage, 'Ha errado el proceso.', 'error');
                return;
            }

            const result = await response.json();
            closeModal();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${result.message || 'Registro guardado.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(modalMessage, 'No se pudo conectar con el servidor.', 'error');
            showMessage(pageMessage, 'Ha errado el proceso.', 'error');
        } finally {
            saveButton.disabled = kind === 'applicant' && !activeRow;
        }
    });

    closeModal();
};

const setupSimpleCrud = () => {
    const modal = document.querySelector('[data-simple-crud]');
    const form = document.getElementById('simple-form');

    if (!modal || !form) {
        return;
    }

    const resultsContainer = document.querySelector('[data-results]');
    const pageMessage = document.getElementById('page-message');
    const modalMessage = document.getElementById('simple-modal-message');
    const saveButton = document.getElementById('save-simple');
    const title = document.getElementById('simple-modal-title');
    const fields = Object.fromEntries([...form.querySelectorAll('[data-simple-field]')].map((field) => [field.dataset.simpleField, field]));
    const cupoInputs = [...form.querySelectorAll('[data-cupo-input]')];
    const currentStudentsInputs = [...form.querySelectorAll('[data-current-students-input]')];
    const ponderacionInputs = [...form.querySelectorAll('[data-ponderacion-input]')];
    let activeRow = null;

    const setValue = (name, value = '') => {
        if (fields[name]) {
            fields[name].value = value || '';
        }
    };

    const resetAdmissionRules = () => {
        cupoInputs.forEach((input) => {
            input.value = input.dataset.defaultValue || '0';
        });
        currentStudentsInputs.forEach((input) => {
            input.value = input.dataset.defaultValue || '0';
        });
        ponderacionInputs.forEach((input) => {
            input.value = input.dataset.defaultValue || '0';
        });
    };

    const fillAdmissionRules = (row = null) => {
        resetAdmissionRules();

        if (!row) {
            return;
        }

        const cupos = JSON.parse(row.dataset.cupos || '[]');
        const ponderaciones = JSON.parse(row.dataset.ponderaciones || '[]');

        cupoInputs.forEach((input) => {
            const cupo = cupos.find((item) => Number(item.id_carrera) === Number(input.dataset.careerId));
            input.value = cupo?.cantidad_cupos ?? input.dataset.defaultValue ?? '0';
        });

        currentStudentsInputs.forEach((input) => {
            const cupo = cupos.find((item) => Number(item.id_carrera) === Number(input.dataset.careerId));
            input.value = cupo?.cantidad_estudiantes ?? input.dataset.defaultValue ?? '0';
        });

        ponderacionInputs.forEach((input) => {
            const ponderacion = ponderaciones.find((item) => Number(item.numero_examen) === Number(input.dataset.examNumber));
            input.value = ponderacion?.ponderacion ?? input.dataset.defaultValue ?? '0';
        });
    };

    const admissionRulesPayload = () => ({
        cupos: cupoInputs.map((input) => ({
            id_carrera: input.dataset.careerId,
            cantidad_cupos: input.value,
            cantidad_estudiantes: currentStudentsInputs.find((studentsInput) => studentsInput.dataset.careerId === input.dataset.careerId)?.value || '0',
        })),
        ponderaciones: ponderacionInputs.map((input) => ({
            numero_examen: input.dataset.examNumber,
            ponderacion: input.value,
        })),
    });

    const openModal = (row = null, readOnly = false) => {
        activeRow = readOnly ? null : row;
        clearMessage(modalMessage);
        form.reset();
        resetAdmissionRules();

        if (readOnly) {
            title.textContent = 'Consultar parametro';
            form.querySelectorAll('input, select, textarea, button[type="submit"]').forEach((el) => {
                if (el !== saveButton) {
                    el.disabled = true;
                }
            });
            saveButton.hidden = true;
        } else {
            title.textContent = row ? 'Modificar parametro' : 'Nuevo parametro';
            form.querySelectorAll('input, select, textarea').forEach((el) => { el.disabled = false; });
            saveButton.hidden = false;
        }

        Object.keys(fields).forEach((name) => setValue(name, row?.dataset[name] || ''));
        fillAdmissionRules(row);
        modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
        fields.semestreNombre?.focus();
    };

    const closeModal = () => {
        activeRow = null;
        clearMessage(modalMessage);
        form.reset();
        form.querySelectorAll('input, select, textarea').forEach((el) => { el.disabled = false; });
        saveButton.hidden = false;
        title.textContent = 'Nuevo parametro';
        resetAdmissionRules();
        fields.semestreNombre?.focus();
    };

    const payload = () => ({
        ...Object.fromEntries([...form.elements].filter((field) => field.name).map((field) => [field.name, field.value])),
        ...admissionRulesPayload(),
    });

    document.querySelectorAll('[data-simple-close]').forEach((button) => button.addEventListener('click', closeModal));

    resultsContainer?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-simple-action]');

        if (!button) {
            return;
        }

        const row = button.closest('tr');

        if (button.dataset.simpleAction === 'view') {
            openModal(row, true);
            return;
        }

        if (button.dataset.simpleAction === 'edit') {
            openModal(row);
            return;
        }

        if (!confirm('Eliminar este parametro?')) {
            return;
        }

        button.disabled = true;
        clearMessage(pageMessage);

        try {
            const response = await fetch(row.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                return;
            }

            const result = await response.json();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${result.message || 'Registro eliminado.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
        } finally {
            button.disabled = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearMessage(modalMessage);
        saveButton.disabled = true;

        try {
            const response = await fetch(activeRow?.dataset.updateUrl || form.dataset.storeUrl, {
                method: activeRow ? 'PATCH' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload()),
            });

            if (!response.ok) {
                showMessage(modalMessage, await readErrors(response), 'error');
                showMessage(pageMessage, 'Ha errado el proceso.', 'error');
                return;
            }

            const result = await response.json();
            closeModal();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${result.message || 'Registro guardado.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(modalMessage, 'No se pudo conectar con el servidor.', 'error');
            showMessage(pageMessage, 'Ha errado el proceso.', 'error');
        } finally {
            saveButton.disabled = false;
        }
    });

    closeModal();
};

const setupScheduleTemplates = () => {
    const workspace = document.getElementById('template-workspace');
    const form = document.getElementById('template-form');

    if (!workspace || !form) {
        return;
    }

    const resultsContainer = document.querySelector('[data-results]');
    const pageMessage = document.getElementById('page-message');
    const modalMessage = document.getElementById('template-modal-message');
    const saveButton = document.getElementById('save-template');
    const title = document.getElementById('template-modal-title');
    const name = document.getElementById('template-name');
    const shift = document.getElementById('template-shift');
    const details = document.getElementById('template-details');
    const detailPreview = document.getElementById('template-detail-preview');
    const customDurationField = document.querySelector('.custom-duration-field');
    const detailInputs = {
        dias: document.querySelector('[data-template-input="dias"]'),
        hora_inicio: document.querySelector('[data-template-input="hora_inicio"]'),
        duracion: document.querySelector('[data-template-input="duracion"]'),
        duracion_custom: document.querySelector('[data-template-input="duracion_custom"]'),
        id_materia: document.querySelector('[data-template-input="id_materia"]'),
        modalidad: document.querySelector('[data-template-input="modalidad"]'),
    };
    let activeRow = null;
    let scheduleDetails = [];

    const dayOptions = [
        [1, 'Lunes'],
        [2, 'Martes'],
        [3, 'Miercoles'],
        [4, 'Jueves'],
        [5, 'Viernes'],
        [6, 'Sabado'],
        [7, 'Domingo'],
    ];

    detailInputs.dias.innerHTML = dayOptions
        .map(([value, label]) => `
            <label class="day-chip">
                <input type="checkbox" value="${value}" data-template-day>
                <span>${label.slice(0, 3)}</span>
            </label>
        `)
        .join('');

    const selectedDays = () => [...detailInputs.dias.querySelectorAll('[data-template-day]:checked')].map((input) => input.value);

    const setSelectedDays = (days = [1]) => {
        const selected = days.map(String);
        detailInputs.dias.querySelectorAll('[data-template-day]').forEach((input) => {
            input.checked = selected.includes(input.value);
        });
    };

    const minutesFromTime = (time) => {
        const [hours, minutes] = `${time}`.split(':').map(Number);

        return (hours * 60) + minutes;
    };

    const timeFromMinutes = (minutes) => {
        const normalized = Math.max(0, Math.min(minutes, 24 * 60));
        const hours = String(Math.floor(normalized / 60)).padStart(2, '0');
        const mins = String(normalized % 60).padStart(2, '0');

        return `${hours}:${mins}`;
    };

    const currentDuration = () => {
        if (detailInputs.duracion.value !== 'custom') {
            return Number(detailInputs.duracion.value || 0);
        }

        return Number(detailInputs.duracion_custom.value || 0);
    };

    const formatDays = (days) => days
        .map((day) => dayOptions.find(([value]) => Number(value) === Number(day))?.[1] || day)
        .join(', ');

    const buildDetailFromComposer = (day = selectedDays()[0]) => {
        const start = detailInputs.hora_inicio.value;
        const duration = currentDuration();
        const subjectOption = detailInputs.id_materia?.selectedOptions?.[0];

        if (!day || !start || duration <= 0 || !detailInputs.id_materia?.value) {
            return null;
        }

        return {
            dia: String(day),
            hora_inicio: start,
            hora_fin: timeFromMinutes(minutesFromTime(start) + duration),
            id_materia: detailInputs.id_materia.value,
            materia_nombre: subjectOption?.textContent?.trim() || '',
            modalidad: detailInputs.modalidad.value,
        };
    };

    const findOverlap = (candidate, ignoreIndex = null) => {
        const candidateStart = minutesFromTime(candidate.hora_inicio);
        const candidateEnd = minutesFromTime(candidate.hora_fin);

        return scheduleDetails.find((detail, index) => {
            if (index === ignoreIndex || Number(detail.dia) !== Number(candidate.dia)) {
                return false;
            }

            const start = minutesFromTime(detail.hora_inicio);
            const end = minutesFromTime(detail.hora_fin);

            return candidateStart < end && candidateEnd > start;
        });
    };

    const findAnyOverlap = () => scheduleDetails.find((detail, index) => findOverlap(detail, index));

    const firstFreeStart = (day, duration) => {
        const occupied = scheduleDetails
            .filter((detail) => Number(detail.dia) === Number(day))
            .map((detail) => ({
                start: minutesFromTime(detail.hora_inicio),
                end: minutesFromTime(detail.hora_fin),
            }))
            .sort((a, b) => a.start - b.start);

        let cursor = 7 * 60;

        for (const block of occupied) {
            if (cursor + duration <= block.start) {
                return cursor;
            }

            cursor = Math.max(cursor, block.end);
        }

        return cursor + duration < 24 * 60 ? cursor : null;
    };

    const updateDetailPreview = () => {
        const days = selectedDays();
        const duration = currentDuration();
        const firstDay = days[0] || 1;
        const nextStart = duration > 0 ? firstFreeStart(firstDay, duration) : null;
        const candidates = days.map((day) => buildDetailFromComposer(day)).filter(Boolean);
        const candidate = candidates[0];

        customDurationField.hidden = detailInputs.duracion.value !== 'custom';

        if (!detailPreview) {
            return;
        }

        if (!days.length) {
            detailPreview.innerHTML = '<span class="schedule-warning">Seleccione al menos un dia.</span>';
            return;
        }

        if (detailInputs.duracion.value === 'custom' && duration <= 0) {
            detailPreview.innerHTML = '<span class="schedule-warning">Indique la duracion manual en minutos.</span>';
            return;
        }

        if (!candidate) {
            detailPreview.innerHTML = nextStart === null
                ? '<span class="muted">No hay hueco libre para esa duracion en el dia elegido.</span>'
                : `<button class="secondary compact" type="button" data-template-use-gap="${timeFromMinutes(nextStart)}">Usar ${timeFromMinutes(nextStart)}</button>`;
            return;
        }

        const overflow = candidates.find((item) => minutesFromTime(item.hora_fin) >= 24 * 60);
        const collisions = candidates
            .map((item) => ({ item, overlap: findOverlap(item) }))
            .filter(({ overlap }) => overlap);
        const end = minutesFromTime(candidate.hora_fin);

        if (overflow || end >= 24 * 60) {
            detailPreview.innerHTML = '<span class="schedule-warning">El bloque no puede pasar de las 24:00.</span>';
            return;
        }

        if (collisions.length) {
            const names = formatDays(collisions.map(({ item }) => item.dia));
            const first = collisions[0].overlap;
            detailPreview.innerHTML = `<span class="schedule-warning">${names}: choca con ${first.hora_inicio} - ${first.hora_fin}. El solape minimo tambien se rechaza.</span>`;
            return;
        }

        detailPreview.innerHTML = `<span class="schedule-ok">Se agregara ${candidate.materia_nombre} ${candidate.hora_inicio} - ${candidate.hora_fin} en ${formatDays(days)}.</span>`;
    };

    const renderPlanner = () => {
        details.innerHTML = '';
        const materiaColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#e0e7ff', '#ccfbf1', '#f5f5f4', '#ffedd5'];

        dayOptions.forEach(([day, label]) => {
            const column = document.createElement('div');
            column.className = 'day-column';
            column.innerHTML = `<h4>${label}</h4>`;

            const dayDetails = scheduleDetails
                .map((detail, index) => ({ ...detail, index }))
                .filter((detail) => Number(detail.dia) === day)
                .sort((a, b) => `${a.hora_inicio}`.localeCompare(`${b.hora_inicio}`));

            if (!dayDetails.length) {
                const empty = document.createElement('span');
                empty.className = 'muted';
                empty.textContent = 'Sin bloques';
                column.appendChild(empty);
            }

            dayDetails.forEach((detail) => {
                const block = document.createElement('div');
                block.className = 'schedule-block';
                const color = detail.id_materia ? materiaColors[Number(detail.id_materia) % materiaColors.length] : '';
                block.style.borderLeft = color ? `4px solid ${color}` : '';
                block.style.background = color || '';
                block.innerHTML = `
                    <strong>${detail.hora_inicio} - ${detail.hora_fin}</strong>
                    <span>${detail.materia_nombre || 'Sin materia'} / ${detail.modalidad}</span>
                    <button class="danger" type="button" data-remove-detail="${detail.index}">Quitar</button>
                `;
                column.appendChild(block);
            });

            details.appendChild(column);
        });

        updateDetailPreview();
    };

    const addCurrentDetail = () => {
        const days = selectedDays();
        const newDetails = days.map((day) => buildDetailFromComposer(day)).filter(Boolean);

        if (!days.length) {
            showMessage(modalMessage, 'Seleccione al menos un dia para el bloque.', 'error');
            return;
        }

        if (!newDetails.length) {
            showMessage(modalMessage, 'Complete la hora de inicio, duracion y materia del bloque.', 'error');
            return;
        }

        const invalid = newDetails.find((detail) => (
            minutesFromTime(detail.hora_fin) <= minutesFromTime(detail.hora_inicio)
            || minutesFromTime(detail.hora_fin) >= 24 * 60
        ));

        if (invalid) {
            showMessage(modalMessage, 'Revise la duracion: el bloque debe terminar antes de las 24:00.', 'error');
            return;
        }

        const collisions = newDetails
            .map((detail) => ({ detail, overlap: findOverlap(detail) }))
            .filter(({ overlap }) => overlap);

        if (collisions.length) {
            const daysWithCollision = formatDays(collisions.map(({ detail }) => detail.dia));
            const first = collisions[0].overlap;
            showMessage(modalMessage, `${daysWithCollision}: el bloque se solapa con ${first.hora_inicio} - ${first.hora_fin}.`, 'error');
            return;
        }

        clearMessage(modalMessage);
        scheduleDetails.push(...newDetails);
        detailInputs.hora_inicio.value = '';
        renderPlanner();
    };

    const findDuplicateName = () => {
        const typed = name.value.trim().toLowerCase();

        if (!typed) {
            return null;
        }

        return [...document.querySelectorAll('.template-row')].find((row) => (
            row !== activeRow && (row.dataset.nombre || '').trim().toLowerCase() === typed
        ));
    };

    const showNameFeedback = () => {
        const duplicate = findDuplicateName();

        if (duplicate) {
            showMessage(modalMessage, 'Ya existe una plantilla visible con ese nombre. Use otro nombre antes de guardar.', 'error');
            return false;
        }

        if (modalMessage.textContent.includes('plantilla visible con ese nombre')) {
            clearMessage(modalMessage);
        }

        return true;
    };

    const openEditor = (row = null) => {
        activeRow = row;
        clearMessage(modalMessage);
        form.reset();
        title.textContent = row ? 'Modificar plantilla' : 'Nueva plantilla';
        document.getElementById('template-modal-subtitle').textContent = row ? 'Ajuste los bloques sin salir de la lista.' : 'Arme bloques para uno o varios dias a la vez.';
        name.value = row?.dataset.nombre || '';
        shift.value = row?.dataset.turno || shift.options[0]?.value || '';
        scheduleDetails = row?.dataset.detalles ? JSON.parse(row.dataset.detalles) : [];
        setSelectedDays([1]);
        renderPlanner();
        workspace.hidden = false;
        workspace.classList.add('is-open');
        workspace.scrollIntoView({ behavior: 'smooth', block: 'start' });
        name.focus();
    };

    const closeEditor = () => {
        activeRow = null;
        clearMessage(modalMessage);
        form.reset();
        title.textContent = 'Nueva plantilla';
        document.getElementById('template-modal-subtitle').textContent = 'Arme bloques para uno o varios dias a la vez.';
        scheduleDetails = [];
        setSelectedDays([1]);
        renderPlanner();
        name.focus();
    };

    const payload = () => ({
        nombre: name.value,
        turno: shift.value,
        detalles: scheduleDetails,
    });

    document.querySelectorAll('[data-template-close]').forEach((button) => button.addEventListener('click', closeEditor));
    document.querySelector('[data-template-add-detail]')?.addEventListener('click', addCurrentDetail);
    Object.values(detailInputs).forEach((input) => input?.addEventListener('input', updateDetailPreview));
    Object.values(detailInputs).forEach((input) => input?.addEventListener('change', updateDetailPreview));
    name.addEventListener('input', showNameFeedback);
    detailInputs.dias.addEventListener('change', updateDetailPreview);
    detailPreview?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-template-use-gap]');

        if (!button) {
            return;
        }

        detailInputs.hora_inicio.value = button.dataset.templateUseGap;
        updateDetailPreview();
    });
    details.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-detail]');

        if (!button) {
            return;
        }

        scheduleDetails.splice(Number(button.dataset.removeDetail), 1);
        renderPlanner();
    });

    resultsContainer?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-template-action]');

        if (!button) {
            return;
        }

        const row = button.closest('tr');

        if (button.dataset.templateAction === 'edit') {
            openEditor(row);
            return;
        }

        if (!confirm(`Eliminar la plantilla ${row.dataset.nombre}?`)) {
            return;
        }

        button.disabled = true;
        clearMessage(pageMessage);

        try {
            const response = await fetch(row.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                showMessage(pageMessage, `${await readErrors(response)} Ha errado el proceso.`, 'error');
                return;
            }

            const result = await response.json();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${result.message || 'Plantilla eliminada.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(pageMessage, 'No se pudo conectar con el servidor. Ha errado el proceso.', 'error');
        } finally {
            button.disabled = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearMessage(modalMessage);

        const overlap = findAnyOverlap();

        if (!showNameFeedback()) {
            return;
        }

        if (overlap) {
            showMessage(modalMessage, `Hay bloques del mismo dia que se solapan alrededor de ${overlap.hora_inicio} - ${overlap.hora_fin}.`, 'error');
            return;
        }

        saveButton.disabled = true;

        try {
            const response = await fetch(activeRow?.dataset.updateUrl || form.dataset.storeUrl, {
                method: activeRow ? 'PATCH' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload()),
            });

            if (!response.ok) {
                showMessage(modalMessage, await readErrors(response), 'error');
                showMessage(pageMessage, 'Ha errado el proceso.', 'error');
                return;
            }

            const result = await response.json();
            closeEditor();
            await resultsContainer.reloadResults?.();
            showMessage(pageMessage, `${result.message || 'Plantilla guardada.'} Se ha completado el proceso.`);
        } catch (error) {
            showMessage(modalMessage, 'No se pudo conectar con el servidor.', 'error');
            showMessage(pageMessage, 'Ha errado el proceso.', 'error');
        } finally {
            saveButton.disabled = false;
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupSidebar();
    setupAjaxFilters();
    setupCredentials();
    setupTeachers();
    setupPersonCrud();
    setupSimpleCrud();
    setupScheduleTemplates();
    setupExportButtons();
    setupPagos();
});

const setupPagos = () => {
    const detailPanel = document.getElementById('pago-detail-workspace');
    const resultsContainer = document.querySelector('[data-results]');

    if (!detailPanel || !resultsContainer) {
        return;
    }

    const fields = {
        codigoOrden: document.getElementById('detail-codigo-orden'),
        monto: document.getElementById('detail-monto'),
        fechaPago: document.getElementById('detail-fecha-pago'),
        estado: document.getElementById('detail-estado'),
        numeroTransaccion: document.getElementById('detail-numero-transaccion'),
        metodoPago: document.getElementById('detail-metodo-pago'),
        mensajeError: document.getElementById('detail-mensaje-error'),
        postulante: document.getElementById('detail-postulante'),
        postulanteCi: document.getElementById('detail-postulante-ci'),
        postulanteRegistro: document.getElementById('detail-postulante-registro'),
        postulanteLibreta: document.getElementById('detail-postulante-libreta'),
        postulanteTitulo: document.getElementById('detail-postulante-titulo'),
    };

    const closeDetail = () => {
        detailPanel.hidden = true;
    };

    document.querySelector('[data-pago-detail-close]')?.addEventListener('click', closeDetail);

    resultsContainer.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pago-action="detail"]');

        if (!button) {
            return;
        }

        const row = button.closest('.pago-row');

        Object.entries(fields).forEach(([key, field]) => {
            if (field && row) {
                field.value = row.dataset[key] || '';
            }
        });

        detailPanel.hidden = false;
        detailPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
};

const setupExportButtons = () => {
    document.querySelectorAll('[data-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();

            const panel = button.closest('.panel');
            const format = button.dataset.export;
            const baseUrl = button.href.split('?')[0];
            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('formato', format);

            if (panel) {
                panel.querySelectorAll('[data-filter-field]').forEach((field) => {
                    const value = field.value?.trim?.() ?? field.value;

                    if (value !== '') {
                        url.searchParams.set(field.name, value);
                    }
                });
            }

            if (format === 'pdf') {
                window.open(url.toString(), '_blank');
            } else {
                window.location.href = url.toString();
            }
        });
    });
};
