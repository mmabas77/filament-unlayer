<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @pushOnce('styles')
        <link rel="stylesheet" href="{{ asset('vendor/filament-unlayer/grapes.min.css') }}"/>
    @endPushOnce

    @php
        $editorId  = 'ge_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $getId());
        $statePath = $getStatePath();
        $mergeTags = $getMergeTags();
    @endphp

    @push('scripts')
    <script>
    (function () {
        var COMPONENT    = {{ \Illuminate\Support\Js::from($editorId) }};
        var STATE_PATH   = {{ \Illuminate\Support\Js::from($statePath) }};
        var MERGE_TAGS   = {{ \Illuminate\Support\Js::from($mergeTags) }};
        var CONTAINER_ID = 'gjs-editor-' + COMPONENT;

        // Load GrapesJS scripts once (self-hosted, no CDN)
        function loadScripts(urls, callback) {
            var loaded = 0;
            urls.forEach(function (url) {
                if (document.querySelector('script[src="' + url + '"]')) {
                    loaded++;
                    if (loaded === urls.length) callback();
                    return;
                }
                var s = document.createElement('script');
                s.src = url;
                s.onload = function () {
                    loaded++;
                    if (loaded === urls.length) callback();
                };
                document.head.appendChild(s);
            });
        }

        var BASE = '{{ asset('vendor/filament-unlayer') }}';

        function makeGrapesComponent() {
            return {
                editorReady: false,
                isSaving: false,
                initialLoadDone: false,
                _changeTimer: null,

                init: function () {
                    var self = this;

                    loadScripts([
                        BASE + '/grapes.min.js',
                        BASE + '/grapesjs-preset-newsletter.js',
                    ], function () {
                        self.bootEditor();
                    });

                    document.addEventListener('submit', function (e) {
                        var container = document.getElementById(CONTAINER_ID);
                        if (container && e.target.contains(container)) {
                            self.handleInterceptedSave(e);
                        }
                    }, true);

                    document.addEventListener('click', function (e) {
                        var btn = e.target.closest('button.fi-btn, button[type="submit"]');
                        if (!btn) return;
                        var text = btn.innerText.trim();
                        if (text.includes('Save') || text.includes('Create')) {
                            var container = document.getElementById(CONTAINER_ID);
                            var form = btn.closest('form');
                            if (!form || (container && form.contains(container))) {
                                self.handleInterceptedSave(e);
                            }
                        }
                    }, true);
                },

                handleInterceptedSave: function (e) {
                    if (this.isSaving) return;
                    var editor = this.getEditor();
                    if (!editor || !this.editorReady) return;

                    e.preventDefault();
                    e.stopImmediatePropagation();

                    this.isSaving = true;
                    var self = this;

                    var html   = editor.runCommand('gjs-get-inlined-html');
                    var design = editor.getProjectData();

                    this.$wire.call('syncUnlayerExport', html, design)
                        .finally(function () {
                            self.isSaving = false;
                        });
                },

                getEditor: function () {
                    return (window._gjs_editors && window._gjs_editors[CONTAINER_ID])
                        ? window._gjs_editors[CONTAINER_ID]
                        : null;
                },

                loadDesign: function (rawState) {
                    var editor = this.getEditor();
                    if (!editor || !this.editorReady || !rawState) return;
                    try {
                        var parsed = (typeof rawState === 'string') ? JSON.parse(rawState) : rawState;
                        var design = parsed.design || null;
                        var html   = parsed.html   || '';

                        // GrapesJS project data has a 'pages' key
                        // Unlayer legacy data has a 'body' key — fall back to HTML
                        if (design && design.pages) {
                            editor.loadProjectData(design);
                        } else if (html) {
                            editor.setComponents(html);
                        }

                        var self = this;
                        setTimeout(function () { self.initialLoadDone = true; }, 1500);
                    } catch (err) {
                        console.error('[GrapesEditor] Load failed:', err);
                        this.initialLoadDone = true;
                    }
                },

                bootEditor: function () {
                    if (window._gjs_editors && window._gjs_editors[CONTAINER_ID]) return;
                    window._gjs_editors = window._gjs_editors || {};

                    var container = document.getElementById(CONTAINER_ID);
                    if (!container) return;

                    var self = this;
                    var plugin = window['grapesjs-preset-newsletter'];

                    try {
                        var editor = grapesjs.init({
                            container: '#' + CONTAINER_ID,
                            height: '100%',
                            storageManager: false,
                            plugins: [plugin],
                            pluginsOpts: {
                                [plugin]: {
                                    inlineCss: true,
                                    showBlocksOnLoad: true,
                                }
                            },
                        });

                        window._gjs_editors[CONTAINER_ID] = editor;
                        self.editorReady = true;

                        // Load existing design once editor is ready
                        editor.on('load', function () {
                            var raw = self.$wire.get(STATE_PATH);
                            self.loadDesign(raw);
                        });

                        // Debounced auto-sync on any change
                        editor.on('update', function () {
                            if (!self.initialLoadDone) return;
                            clearTimeout(self._changeTimer);
                            self._changeTimer = setTimeout(function () {
                                var html   = editor.runCommand('gjs-get-inlined-html');
                                var design = editor.getProjectData();
                                self.$wire.set(STATE_PATH, JSON.stringify({
                                    design: design,
                                    html:   html,
                                }), false);
                            }, 600);
                        });

                    } catch (e) {
                        console.error('[GrapesEditor] Boot error:', e);
                    }
                },
            };
        }

        function register() {
            Alpine.data(COMPONENT, makeGrapesComponent);
        }

        if (window.Alpine && window.Alpine.data) {
            register();
        } else {
            document.addEventListener('alpine:init', register);
        }
    })();
    </script>
    @endpush

    <div
        x-data="{{ $editorId }}"
        wire:ignore
        class="border border-gray-300 rounded-lg overflow-hidden shadow-sm dark:border-gray-700"
        style="height: 750px;"
    >
        <div id="gjs-editor-{{ $editorId }}" style="height: 100%; width: 100%;"></div>
    </div>
</x-dynamic-component>
