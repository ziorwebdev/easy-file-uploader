import { create, registerPlugin } from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';

import 'filepond/dist/filepond.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
import "./style.css";

// Array of FilePond plugins to register
let filePondPlugins = [
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType,
    FilePondPluginImagePreview
];

// Allow developers to modify the plugin list via "wp_filepond_filepond_plugins" filter
filePondPlugins = wpFilePond.applyFilters("wp_filepond_filepond_plugins", filePondPlugins);

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
                onerror: (response) => console.error("Upload error:", response),
                onload: (response) => {
                    try {
                        const json = JSON.parse(response);
                        return json.success ? json?.data?.url ?? "" : "";
                    } catch {
                        return "";
                    }
                },
                ondata: (formData) => {
                    formData.append("secret_key", secret_key);

                    return formData;
                },
                headers: {
                    'X-WP-Nonce': configuration.nonce
                },
                url: `${configuration.ajaxUrl}?action=filepond_wp_integration_upload` 
            },
            revert: {
                headers: {
                    'X-WP-Nonce': configuration.nonce
                },
                method: "POST",
                url: `${configuration.ajaxUrl}?action=filepond_wp_integration_remove`
            }
        }
    };

    return wpFilePond.applyFilters("wp_filepond_configuration", Object.assign({}, configuration, defaultConfiguration));
}

function createFilePondInstance(fileInput, configuration = {}) {
    const FilePondConfiguration = getFilePondConfiguration(configuration);

    return create(fileInput, FilePondConfiguration);
}

export default createFilePondInstance;
