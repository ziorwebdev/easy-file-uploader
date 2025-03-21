import createFilePondInstance from "./dragdrop/main.js";

$(document).ready(function() {
    const filePondInstances = new Map();
    const dragDropUploaderFields = $(".easy-dragdrop-upload");
    let filePondIntegration = EasyDragDropUploader || {};

    filePondIntegration.allowMultiple = "1" === EasyDragDropUploader.allowMultiple;

    /**
     * Raised event before the FilePond instance is created
     * To allow developers to set global FilePond options
     */
    $(document).trigger("easy_dragdrop_before_instance_created", filePondIntegration);

    dragDropUploaderFields.each(function() {
        const inputConfig = getInputConfiguration($(this)) || {};
        const configuration = {...filePondIntegration, ...inputConfig};

        for (const key in configuration) {
            if (! inputConfig[key] && filePondIntegration[key]) {
                configuration[key] = filePondIntegration[key];
            }
        }

        const filePondInstance = createFilePondInstance($(this)[0], configuration);

        /**
         * Raised event when a FilePond instance is created
         * @event easy_dragdrop_instance_created
         * @property {object} filePondInstance - The created FilePond instance
         */
        $(document).trigger("easy_dragdrop_instance_created", filePondInstance);

        filePondInstances.set(this, filePondInstance);
    });

    /**
     * Clears all FilePond instances when an Elementor form submission is successful.
     * @event submit_success
     * @param {Event} event - The event object
     * @param {object} response - The response object from the form submission
     */
    $(document).on("submit_success", function() {
        filePondInstances.forEach((instance) => instance.removeFiles());
    });
});

/**
 * Retrieves input configuration from the file input element.
 * @param {jQuery} fileInput - The jQuery object representing the file input element.
 * @returns {object} The extracted configuration options.
 */
function getInputConfiguration(fileInput) {
    const data = $(fileInput).data();

    return {
        acceptedFileTypes: data.filetypes?.split(",") ?? null,
        allowMultiple: fileInput.attr("multiple") !== undefined,
        labelIdle: data.label ?? "",
        maxFiles: data.maxfiles ?? null,
        maxFileSize: data.filesize ? `${data.filesize}MB` : null
    };
}

function thiss() {
    console.log("Hello World");
}
