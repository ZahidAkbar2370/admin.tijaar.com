{{-- Section modal: separate Laravel form POST (must be outside the main page form). --}}
<div id="{{ $prefix }}-section-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity js-close-section-modal" data-prefix="{{ $prefix }}"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col">
            <form method="POST" action="{{ route('admin.pages.sections.save', $page) }}" id="{{ $prefix }}-section-form" class="flex flex-col flex-1 min-h-0">
                @csrf
                <input type="hidden" name="section_index" id="{{ $prefix }}-section-index" value="{{ old('section_index', '-1') }}" />
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900" id="{{ $prefix }}-section-modal-title">Add section</h3>
                    <button type="button" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition js-close-section-modal" data-prefix="{{ $prefix }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-6 py-4 space-y-4 overflow-y-auto flex-1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section title</label>
                        <input type="text" name="section_title" id="{{ $prefix }}-section-title" value="{{ old('section_title') }}" placeholder="e.g. 1. Introduction" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea name="section_content" id="{{ $prefix }}-section-content" rows="12" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono">{{ old('section_content') }}</textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl text-sm font-medium transition js-close-section-modal" data-prefix="{{ $prefix }}">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-sm font-medium transition">Save section</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (!($ckeditorLoaded ?? false))
    @include('admin.partials.ckeditor')
@endif
<script>
document.addEventListener('DOMContentLoaded', function() {
    var prefix = @json($prefix);
    var modal = document.getElementById(prefix + '-section-modal');
    var form = document.getElementById(prefix + '-section-form');
    var indexInput = document.getElementById(prefix + '-section-index');
    var titleInput = document.getElementById(prefix + '-section-title');
    var contentInput = document.getElementById(prefix + '-section-content');
    var titleHeading = document.getElementById(prefix + '-section-modal-title');
    var dataEl = document.getElementById(prefix + '-sections-data');
    var textareaId = prefix + '-section-content';
    var sectionsData = [];

    if (!modal || !form) {
        return;
    }

    if (dataEl) {
        try { sectionsData = JSON.parse(dataEl.textContent || '[]'); } catch (e) { sectionsData = []; }
    }

    function destroyEditor() {
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[textareaId]) {
            try { CKEDITOR.instances[textareaId].destroy(true); } catch (e) {}
        }
    }

    window['openSectionModal_' + prefix] = function(index) {
        var i = parseInt(index, 10);
        var row = (i >= 0 && sectionsData[i]) ? sectionsData[i] : { title: '', content: '<p></p>' };
        indexInput.value = i >= 0 ? String(i) : '-1';
        titleInput.value = row.title || '';
        contentInput.value = row.content || '<p></p>';
        if (titleHeading) {
            titleHeading.textContent = i >= 0 ? 'Edit section' : 'Add section';
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        destroyEditor();
        setTimeout(function() {
            if (typeof CKEDITOR !== 'undefined') {
                CKEDITOR.replace(textareaId, {
                    height: 280,
                    removePlugins: 'elementspath',
                    resize_enabled: false,
                    toolbar: [['Bold', 'Italic', 'Underline'], ['NumberedList', 'BulletedList'], ['Link'], ['RemoveFormat']],
                    contentsCss: 'data:text/css;charset=utf-8,' + encodeURIComponent(
                        'a, a:link, a:visited { color: #1790d7 !important; text-decoration: underline; text-underline-offset: 2px; font-weight: 500; }' +
                        'a:hover, a:focus { color: #0d6fa8 !important; }'
                    )
                });
            }
        }, 100);
    };

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        destroyEditor();
    }

    document.querySelectorAll('.js-open-section-modal[data-prefix="' + prefix + '"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window['openSectionModal_' + prefix](btn.getAttribute('data-index'));
        });
    });

    document.querySelectorAll('.js-close-section-modal[data-prefix="' + prefix + '"]').forEach(function(btn) {
        btn.addEventListener('click', closeModal);
    });

    form.addEventListener('submit', function() {
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[textareaId]) {
            CKEDITOR.instances[textareaId].updateElement();
        }
    });

    @if (old('section_title') !== null || old('section_content') !== null)
    window['openSectionModal_' + prefix]({{ (int) old('section_index', -1) }});
    @endif
});
</script>
