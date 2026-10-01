/** pulls powerpress save notices into the block editor after each save */
(function (wp) {
    const { select, dispatch, subscribe } = wp.data;
    let wasPostSaving = false;
    let wasSaving = false;

    const clearNotices = () => {
        select('core/notices').getNotices()
            .filter((notice) => String(notice.id).startsWith('powerpress-'))
            .forEach((notice) => dispatch('core/notices').removeNotice(notice.id));
    };

    const pullNotices = () => {
        wp.apiFetch({ path: '/powerpress/v1/editor-notices', method: 'POST' })
            .then((notices) => {
                if (!Array.isArray(notices)) {
                    return;
                }

                notices.forEach((notice) => {
                    dispatch('core/notices').createNotice(notice.status, notice.html, {
                        id: notice.id,
                        isDismissible: true,
                        __unstableHTML: true,
                    });
                });
            })
            .catch((error) => console.warn('powerpress editor notices', error));
    };

    subscribe(() => {
        const editor = select('core/editor');
        const editPost = select('core/edit-post');
        const postSaving = editor.isSavingPost() && !editor.isAutosavingPost();
        const saving = postSaving || (editPost ? editPost.isSavingMetaBoxes() : false);

        if (postSaving && !wasPostSaving) {
            clearNotices();
        }

        if (wasSaving && !saving) {
            pullNotices();
        }

        wasPostSaving = postSaving;
        wasSaving = saving;
    });

    wp.domReady(pullNotices);
})(window.wp);
