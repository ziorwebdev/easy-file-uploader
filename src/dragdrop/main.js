import { create, registerPlugin } from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import wpFilepond from "./helpers.js";

import 'filepond/dist/filepond.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
import "./style.css";

// Array of FilePond plugins to register
let filePondPlugins = [
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType
];

// Allow developers to modify the plugin list via "dragdrop_plugins" filter
filePondPlugins = wpFilepond.applyFilters("dragdrop_plugins", filePondPlugins);

// Register FilePond plugins
registerPlugin(...filePondPlugins);

function convertToBytes(sizeString) {
    const units = { B: 1, KB: 1024, MB: 1024 ** 2, GB: 1024 ** 3 };
    const match = sizeString.match(/^(\d+)(B|KB|MB|GB)$/i);

    if (!match) return null; // Invalid format

    const value = parseInt(match[1], 10);
    const unit = match[2].toUpperCase();

    return value * units[unit];
}

function encryptData(data) {
    data = JSON.stringify(data);

    return btoa(data);
}

function getFilePondConfiguration(configuration) {
    const secret_key = encryptData({
        types: configuration.acceptedFileTypes.join(","),
        size: convertToBytes(configuration.maxFileSize)
    });

    const defaultConfiguration = {
        credits: false,
        fileValidateTypeLabelExpectedTypes: "",
        labelMaxFileSize: "",
        server: {
            process: {
                method: "POST",
                onerror: (response) => {
                    const responseItem = JSON.parse(response);

                    $(document).trigger("dragdrop_upload_error", responseItem);
                    
                    return responseItem?.data?.error ?? "";
                },  
                onload: (response) => {
                    const responseItem = JSON.parse(response);

                    // Trigger dragdrop_upload_success event
                    $(document).trigger("dragdrop_upload_success", responseItem);

                    return responseItem.success ? responseItem?.data?.file_id ?? "" : "";
                },
                ondata: (formData) => {
                    formData.append("secret_key", secret_key);

                    return formData;
                },
                headers: {
                    'X-WP-Nonce': configuration.nonce
                },
                url: `${configuration.ajaxUrl}?action=dragdrop_upload` 
            },
            revert: {
                headers: {
                    'X-WP-Nonce': configuration.nonce
                },
                // Use POST method rather than DELETE to add nonce in the header
                method: "POST",
                url: `${configuration.ajaxUrl}?action=dragdrop_remove`
            }
        }
    };

    return wpFilepond.applyFilters("dragdrop_configuration", Object.assign({}, configuration, defaultConfiguration));
}

function createFilePondInstance(fileInput, configuration = {}) {
    const FilePondConfiguration = getFilePondConfiguration(configuration);

    return create(fileInput, FilePondConfiguration);
}

function revertFile() {

}

export default createFilePondInstance;
