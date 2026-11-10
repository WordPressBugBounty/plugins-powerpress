/** image picker on wp.media, a data-pp-picker button fills data-pp-target w/ the image url and data-pp-id-target w/ its attachment id */
(function () {
    'use strict';

    const Select = wp.media.view.MediaFrame.Select;
    const ACCEPTED = ['image/jpeg', 'image/png'];
    const frames = {};
    let activeButton = null;

    /** media library w/ upload + insert from url, nothing else */
    const ImageFrame = Select.extend({
        /** library + embed states */
        createStates: function () {
            this.states.add([
                new wp.media.controller.Library({
                    title: this.options.title,
                    library: wp.media.query(this.options.library),
                    multiple: false,
                    priority: 20,
                }),
                new wp.media.controller.Embed({ metadata: {} }),
            ]);
        },

        /** select handlers + the post window's insert from url view */
        bindHandlers: function () {
            Select.prototype.bindHandlers.apply(this, arguments);
            this.on('content:render:embed', wp.media.view.MediaFrame.Post.prototype.embedContent, this);
            this.on('toolbar:create:main-embed', this.embedToolbar, this);
        },

        /** insert from url toolbar w/ the picker's button label */
        embedToolbar: function (toolbar) {
            toolbar.view = new wp.media.view.Toolbar.Embed({
                controller: this,
                text: this.options.button.text,
            });
        },
    });

    /** pick written into the active button's inputs, change fired so the row preview updates */
    function fill(url, id) {
        const urlInput = document.getElementById(activeButton.dataset.ppTarget);
        const idTarget = activeButton.dataset.ppIdTarget;
        const idInput = idTarget ? document.getElementById(idTarget) : null;

        if (idInput) {
            idInput.value = id ? String(id) : '';
        }

        if (urlInput) {
            urlInput.value = url;
            urlInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /** one window per picker kind, labels from the button */
    function pickerFrame(button) {
        const kind = button.dataset.ppPicker;
        if (frames[kind]) {
            return frames[kind];
        }

        const frame = new ImageFrame({
            title: button.dataset.ppTitle,
            button: { text: button.dataset.ppButton },
            library: { type: ACCEPTED },
            multiple: false,
        });

        frame.on('select', function () {
            // INSERT FROM URL
            const state = frame.state();
            if (state.id === 'embed') {
                fill(state.props.get('url'), '');
                return;
            }

            // LIBRARY PICK, JPEG OR PNG ONLY SINCE UPLOADS SKIP THE LIBRARY FILTER
            const picked = state.get('selection').first();
            if (!picked) {
                return;
            }

            if (ACCEPTED.indexOf(picked.get('mime')) === -1) {
                window.alert(activeButton.dataset.ppTypeError);
                return;
            }

            fill(picked.get('url'), picked.get('id'));
        });

        frames[kind] = frame;
        return frame;
    }

    // ONE LISTENER FOR EVERY PICKER BUTTON, ROWS ADDED LATER INCLUDED
    document.addEventListener('click', function (event) {
        const button = event.target;
        if (!button.dataset || !button.dataset.ppPicker) {
            return;
        }

        event.preventDefault();
        activeButton = button;
        pickerFrame(button).open();
    });
})();
