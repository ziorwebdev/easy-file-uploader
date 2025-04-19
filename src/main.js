import createdragDropInstance from "./dragdrop/main.js";

$(document).ready(function () {
    const submitButton = $("form").find(".elementor-field-type-submit .elementor-button");
    const submitButtonText = $(submitButton).find(".elementor-button-text").text();
    const dragDropInstances = new Map();
    const dragDropUploaderFields = $(".easy-dragdrop-upload");
    const dragDropUploader = EasyDragDropUploader || {};

    dragDropUploader.allowMultiple = "1" === EasyDragDropUploader.allowMultiple;

    /**
     * Raised event before the FilePond instance is created
     * To allow developers to set global FilePond options
     */
    $(document).trigger("easy_dragdrop_before_instance_created", dragDropUploader);

    dragDropUploaderFields.each(function() {
        const inputConfig = getInputConfiguration($(this)) || {};
        const configuration = { ...dragDropUploader, ...inputConfig };

        for (const key in configuration) {
            if (! inputConfig[key] && dragDropUploader[key]) {
                configuration[key] = dragDropUploader[key];
            }
        }

        const dragDropInstance = createdragDropInstance($(this)[0], configuration);

        /**
         * Update the submit button when a file is added or processed
         */
        dragDropInstance.on("addfile", updateSubmitButton);
        dragDropInstance.on("processfiles", updateSubmitButton);
        /**
         * Raised event when a FilePond instance is created
         * @event easy_dragdrop_instance_created
         * @property {object} filePondInstance - The created FilePond instance
         */
        $(document).trigger("easy_dragdrop_instance_created", dragDropInstance);

        dragDropInstances.set(this, dragDropInstance);
    });

    /**
     * Checks if any FilePond instance is uploading
     * @returns {boolean} True if any instance is uploading, false otherwise
     */
    function hasUploadingInstances() {
        for (const value of dragDropInstances.values()) {
            const files = value.getFiles();
            if (
                files.some(file =>
                    file.status !== 5 &&
                    ![6, 8, 10].includes(file.status)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the submit button when a file is added or processed
     */
    function updateSubmitButton() {
        const isUploading = hasUploadingInstances();

        // If isUploading or this current the current instance, disable the submit button
        if (isUploading) {
            $(submitButton).addClass("easy-dragdrop-upload-button--disabled");
            $(submitButton).prop("disabled", isUploading);
            $(submitButton).find(".elementor-button-text").text("Uploading...");
        } else {
            $(submitButton).removeClass("easy-dragdrop-upload-button--disabled");
            $(submitButton).prop("disabled", false);
            $(submitButton).find(".elementor-button-text").text(submitButtonText);
        }
    }

    /**
     * Clears all FilePond instances when an Elementor form submission is successful.
     * @event submit_success
     * @param {Event} event - The event object
     * @param {object} response - The response object from the form submission
     */
    $(document).on("submit_success", function() {
        dragDropInstances.forEach((instance) => instance.removeFiles());
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
        maxFileSize: data.filesize ? `${data.filesize}MB` : null,
        maxFiles: data.maxfiles ?? null,
    };
}