import {create, registerPlugin} from "filepond";
import FilePondPluginFileValidateSize from "filepond-plugin-file-validate-size";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";
import easyDragDropFileUploader from "./helpers.js";

import "filepond/dist/filepond.css";
import "filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css";
import "./style.css";

// Array of FilePond plugins to register
let filePondPlugins = [
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType
];

// Allow developers to modify the plugin list via "easy_dragdrop_plugins" filter
filePondPlugins = easyDragDropFileUploader.applyFilters("easy_dragdrop_plugins", filePondPlugins);

// Register FilePond plugins
registerPlugin(...filePondPlugins);

/**
 * Converts a file size string (e.g., '10MB') to bytes.
 * @param {string} sizeString - The file size string to convert.
 * @returns {number|null} The file size in bytes, or null if the format is invalid.
 */
function convertToBytes(sizeString) {
    const units = {B: 1, GB: 1024 * 3, KB: 1024, MB: 1024 * 2};
    const match = sizeString.match(/^\(\d+\)(B|KB|MB|GB)$/i);

    if (! match) {
        return null;
    } // Invalid format

    const value = parseInt(match[1], 10);
    const unit = match[2].toUpperCase();

    return value * units[unit];
}

/**
 * Encrypts data using Base64 encoding.
 * @param {object} data - The data object to encrypt.
 * @returns {string} The encrypted Base64 string.
 */
function encryptData(data) {
    data = JSON.stringify(data);
    return btoa(data);
}

/**
 * Generates the FilePond configuration with security settings.
 * @param {object} configuration - The configuration object containing settings like maxFileSize and acceptedFileTypes.
 * @returns {object} The modified FilePond configuration.
 */
function getFilePondConfiguration(configuration) {
    const secretKey = encryptData({
        size: convertToBytes(configuration.maxFileSize),
        types: configuration.acceptedFileTypes.join(",")
    });

    const defaultConfiguration = {
        credits: false,
        fileValidateTypeLabelExpectedTypes: "",
        labelMaxFileSize: "",
        server: {
            process: {
                method: "POST",
                ondata: (formData) => {
                    formData.append("secret_key", secretKey);
                    formData.append("security", configuration.nonce);
                    return formData;
                },
                onerror: (response) => {
                    const responseItem = JSON.parse(response);
                    $(document).trigger("easy_dragdrop_upload_error", responseItem);
                    return responseItem?.data?.error ?? "";
                },
                onload: (response) => {
                    const responseItem = JSON.parse(response);

                    // Trigger easy_dragdrop_upload_success event
                    $(document).trigger("easy_dragdrop_upload_success", responseItem);
                    return responseItem.success ? responseItem?.data?.file_id ?? "" : "";
                },
                url: `${configuration.ajaxUrl}?action=easy_dragdrop_upload`
            },
            revert: {
                url: `${configuration.ajaxUrl}?action=easy_dragdrop_remove&security=${configuration.nonce}`
            }
        }
    };

    return easyDragDropFileUploader.applyFilters("easy_dragdrop_configuration", Object.assign({}, configuration, defaultConfiguration));
}

/**
 * Creates a FilePond instance for a given file input element.
 * @param {HTMLElement} fileInput - The file input element.
 * @param {object} [configuration] - Optional configuration settings.
 * @returns {object} The created FilePond instance.
 */
function createFilePondInstance(fileInput, configuration = {}) {
    const FilePondConfiguration = getFilePondConfiguration(configuration);
    return create(fileInput, FilePondConfiguration);
}

export default createFilePondInstance;
