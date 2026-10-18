<p class="ui-list__errors" data-catalog-status role="status" hidden></p>
<template data-catalog-success>
    <x-ui.feedback variant="dialog" position="corner" title="" :auto-open="false" :duration="1000" id="catalog-async-success" />
</template>
@if (session('catalog-success') && !$errors->any())
    <x-ui.feedback variant="dialog" position="corner" :title="session('catalog-success')" :duration="1000" id="catalog-success" />
    <noscript><p role="status">{{ session('catalog-success') }}</p></noscript>
@endif
<noscript><style>
    .catalog-dialog { display: block; position: static; margin: 15px auto; }
    .catalog-dialog button[type="button"] { display: none; }
    .catalog-dialog[data-formula-editor="update"]:not([data-reopen]) { display: none; }
</style></noscript>
