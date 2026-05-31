<div class="app-modal-grid">
    <div class="field">
        <label for="person-ci">CI</label>
        <input id="person-ci" name="ci" maxlength="20" required data-person-field="ci">
    </div>
    <div class="field">
        <label for="person-nombres">Nombres</label>
        <input id="person-nombres" name="nombres" maxlength="50" required data-person-field="nombres">
    </div>
    <div class="field">
        <label for="person-apellido-paterno">Apellido paterno</label>
        <input id="person-apellido-paterno" name="apellido_paterno" maxlength="50" required data-person-field="apellidoPaterno">
    </div>
    <div class="field">
        <label for="person-apellido-materno">Apellido materno</label>
        <input id="person-apellido-materno" name="apellido_materno" maxlength="50" data-person-field="apellidoMaterno">
    </div>
    <div class="field">
        <label for="person-fecha-nacimiento">Fecha de nacimiento</label>
        <input id="person-fecha-nacimiento" name="fecha_nacimiento" type="date" required data-person-field="fechaNacimiento">
    </div>
    <div class="field">
        <label for="person-sexo">Sexo</label>
        <select id="person-sexo" name="sexo" required data-person-field="sexo">
            <option value="M">Masculino</option>
            <option value="F">Femenino</option>
        </select>
    </div>
    <div class="field">
        <label for="person-correo">Correo</label>
        <input id="person-correo" name="correo" type="email" maxlength="50" required data-person-field="correo">
    </div>
    <div class="field">
        <label for="person-telefono">Telefono</label>
        <input id="person-telefono" name="telefono" maxlength="20" data-person-field="telefono">
    </div>
    <div class="field">
        <label for="person-direccion">Direccion</label>
        <input id="person-direccion" name="direccion" maxlength="70" data-person-field="direccion">
    </div>
</div>
