<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Upload</title>
</head>
<body>
<input type="file" id="fileInput" />
<button id="uploadBtn">Upload</button>
<div id="uploadStatus"></div>
<h3>Uploaded Files:</h3>
<ul id="fileList"></ul>

<script>
    const chunkSize = 1024 * 1024; // 1MB per chunk
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.getElementById('uploadBtn').addEventListener('click', async () => {
        const fileInput = document.getElementById('fileInput');
        const uploadStatus = document.getElementById('uploadStatus');

        if (fileInput.files.length === 0) {
            alert("Please select a file.");
            return;
        }

        const file = fileInput.files[0];
        let initial = 0;
        let chunkNumber = 0;

        uploadStatus.textContent = "Uploading...";

        while (initial < file.size) {
            const chunk = file.slice(initial, initial + chunkSize);
            const formData = new FormData();
            formData.append('file', chunk);
            formData.append('fileName', file.name);
            formData.append('chunkNumber', chunkNumber);
            formData.append('totalChunks', Math.ceil(file.size / chunkSize));

            try {
                await uploadChunk(formData);
                initial += chunkSize;
                chunkNumber++;
            } catch (error) {
                console.error("Error uploading chunk: ", error);
                uploadStatus.textContent = "Upload failed!";
                break;
            }
        }

        uploadStatus.textContent = "Upload completed!";
        await updateFileList();
    });

    async function uploadChunk(formData) {
        return fetch('/upload-chunk', {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            method: 'POST',
            body: formData,
        }).then(response => response.json());
    }

    async function updateFileList() {
        const fileList = document.getElementById('fileList');
        const response = await fetch('/files');
        const files = await response.json();

        fileList.innerHTML = '';
        files.forEach(file => {
            const li = document.createElement('li');
            li.textContent = file;
            fileList.appendChild(li);
        });
    }

    document.addEventListener('DOMContentLoaded', updateFileList);
</script>
</body>
</html>
