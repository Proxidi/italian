@php($subject = $subject ?? null)
@foreach (['nombre' => ['Nombre', 'nombre', 'text', 25], 'apellido_paterno' => ['Apellido Paterno', 'apellido_paterno', 'text', 25], 'apellido_materno' => ['Apellido Materno', 'apellido_materno', 'text', 25], 'edad' => ['Edad', 'edad', 'number', null], 'email' => ['Email', 'email', 'email', 75]] as $name => [$label, $attribute, $type, $max])
    <div class="users-form__field">
        <label for="{{ $prefix }}-{{ $name }}">{{ $label }}:</label>
        <input class="ui-list__input" id="{{ $prefix }}-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $subject?->{$attribute}) }}" @if($max) maxlength="{{ $max }}" @endif @if($type === 'number') min="0" max="127" @endif required @error($name) aria-invalid="true" aria-describedby="{{ $prefix }}-{{ $name }}-error" @enderror>
        @error($name)<span class="users-form__error" id="{{ $prefix }}-{{ $name }}-error">{{ $message }}</span>@enderror
    </div>
    @if($name === 'edad' && !$subject)
        <div class="users-form__field">
            <span class="users-form__label" id="{{ $prefix }}-sexo-label">Sexo:</span>
            <div class="users-form__radios" role="group" aria-labelledby="{{ $prefix }}-sexo-label">
                <label><input type="radio" name="sexo" value="H" @checked(old('sexo', 'H') === 'H') required> Hombre</label>
                <label><input type="radio" name="sexo" value="M" @checked(old('sexo') === 'M')> Mujer</label>
            </div>
            @error('sexo')<span class="users-form__error">{{ $message }}</span>@enderror
        </div>
    @endif
@endforeach
@foreach (['plaza_id' => ['Plaza', $plazas], 'role_id' => ['Rol', $roles]] as $name => [$label, $options])
    <div class="users-form__field">
        <label for="{{ $prefix }}-{{ $name }}">{{ $label }}:</label>
        <select class="ui-list__input" id="{{ $prefix }}-{{ $name }}" name="{{ $name }}" required>
            @if($name === 'role_id' && empty($selectedRoleId))<option value="">Selecciona un rol</option>@endif
            @foreach($options as $option)
                <option value="{{ $option->id }}" @selected((string) old($name, $name === 'role_id' ? ($selectedRoleId ?? null) : $subject?->{$name}) === (string) $option->id)>{{ $option->nombre }}</option>
            @endforeach
        </select>
        @error($name)<span class="users-form__error">{{ $message }}</span>@enderror
    </div>
@endforeach
<div class="users-form__field">
    <label for="{{ $prefix }}-usuario">User Name:</label>
    <input class="ui-list__input" id="{{ $prefix }}-usuario" name="usuario" value="{{ old('usuario', $subject?->usuario) }}" maxlength="50" autocomplete="off" required>
    @error('usuario')<span class="users-form__error">{{ $message }}</span>@enderror
</div>
<div class="users-form__field">
    <label for="{{ $prefix }}-password">Password:</label>
    <input class="ui-list__input" id="{{ $prefix }}-password" name="password" type="password" maxlength="4096" autocomplete="new-password" @required(!$subject) @if($subject) aria-describedby="password-help" @endif>
    @if($subject)<small id="password-help">Deja vacío para conservar la contraseña actual.</small>@endif
    @error('password')<span class="users-form__error">{{ $message }}</span>@enderror
</div>
