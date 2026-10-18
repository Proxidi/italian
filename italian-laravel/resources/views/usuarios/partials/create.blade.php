<dialog class="users-dialog" data-user-create @if($errors->hasAny(['nombre', 'apepat', 'apemat', 'edad', 'sexo', 'email', 'usuario', 'password', 'plaza_id', 'rol_id', 'Password', 'Hash', 'sha1_id'])) data-reopen @endif aria-labelledby="create-user-title">
    <header class="users-dialog__header"><h2 id="create-user-title">Agregar nuevo usuario</h2><button type="button" data-close-user-create aria-label="Cerrar">×</button></header>
    <form class="users-form" action="{{ route('usuarios.store') }}" method="POST">
        @csrf
        @include('usuarios.partials.fields', ['prefix' => 'create'])
        <footer class="users-dialog__footer">
            <button class="ui-list__button ui-list__button--default" type="button" data-close-user-create>Cerrar</button>
            <button class="ui-list__button ui-list__button--blue" type="submit">Guardar datos</button>
        </footer>
    </form>
</dialog>
