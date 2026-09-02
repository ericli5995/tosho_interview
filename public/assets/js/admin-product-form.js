/* Admin image uploader - Vue 3 (global build).
   Enhances the real <input type="file" multiple> so the admin can preview,
   remove, reorder and pick a primary image before the form is submitted.
   The files are rewritten back onto the input via DataTransfer, so the normal
   multipart POST carries exactly what is shown. Degrades to a plain multi-file
   input when this script or DataTransfer is unavailable. */
(function () {
    "use strict";

    var mount = document.getElementById("image-preview");
    var input = document.getElementById("images-input");
    var primaryField = document.getElementById("primary_image_index");
    var dropzone = document.querySelector("#image-uploader .uploader__dropzone");

    if (!mount || !input || !window.Vue) {
        return;
    }

    var supportsDataTransfer = true;
    try {
        new DataTransfer();
    } catch (e) {
        supportsDataTransfer = false;
    }

    Vue.createApp({
        data: function () {
            return {
                items: [],
                primary: 0,
                supportsDT: supportsDataTransfer
            };
        },
        mounted: function () {
            var self = this;

            input.addEventListener("change", function (e) {
                self.addFiles(e.target.files);
            });

            if (dropzone) {
                ["dragenter", "dragover"].forEach(function (type) {
                    dropzone.addEventListener(type, function (e) {
                        e.preventDefault();
                        dropzone.classList.add("is-dragover");
                    });
                });
                ["dragleave", "drop"].forEach(function (type) {
                    dropzone.addEventListener(type, function (e) {
                        e.preventDefault();
                        dropzone.classList.remove("is-dragover");
                    });
                });
                dropzone.addEventListener("drop", function (e) {
                    if (e.dataTransfer && e.dataTransfer.files) {
                        self.addFiles(e.dataTransfer.files);
                    }
                });
            }
        },
        methods: {
            addFiles: function (fileList) {
                var added = false;
                Array.prototype.forEach.call(fileList || [], function (file) {
                    if (file.type.indexOf("image/") !== 0) {
                        return;
                    }
                    this.items.push({
                        file: file,
                        url: URL.createObjectURL(file),
                        name: file.name,
                        size: file.size
                    });
                    added = true;
                }, this);
                if (added) {
                    this.sync();
                }
            },
            remove: function (index) {
                URL.revokeObjectURL(this.items[index].url);
                this.items.splice(index, 1);
                if (this.primary >= this.items.length) {
                    this.primary = 0;
                }
                this.sync();
            },
            move: function (index, delta) {
                var target = index + delta;
                if (target < 0 || target >= this.items.length) {
                    return;
                }
                var moved = this.items.splice(index, 1)[0];
                this.items.splice(target, 0, moved);
                if (this.primary === index) {
                    this.primary = target;
                } else if (this.primary === target) {
                    this.primary = index;
                }
                this.sync();
            },
            setPrimary: function (index) {
                this.primary = index;
                this.syncPrimaryField();
            },
            sync: function () {
                if (this.supportsDT) {
                    var dt = new DataTransfer();
                    this.items.forEach(function (it) { dt.items.add(it.file); });
                    input.files = dt.files;
                }
                this.syncPrimaryField();
            },
            syncPrimaryField: function () {
                if (primaryField) {
                    primaryField.value = this.items.length ? String(this.primary) : "";
                }
            },
            humanSize: function (bytes) {
                if (bytes < 1024) { return bytes + " B"; }
                if (bytes < 1048576) { return Math.round(bytes / 1024) + " KB"; }
                return (bytes / 1048576).toFixed(1) + " MB";
            }
        },
        template: [
            '<div>',
            '  <p v-if="!items.length" class="uploader__hint">選択された画像はありません。</p>',
            '  <ul v-else class="uploader__list">',
            '    <li v-for="(item, i) in items" :key="item.url" class="uploader__item" :class="{ \'is-primary\': i === primary }">',
            '      <img :src="item.url" :alt="item.name" />',
            '      <div class="uploader__meta">',
            '        <span class="uploader__name">{{ item.name }}</span>',
            '        <span class="uploader__size">{{ humanSize(item.size) }}</span>',
            '      </div>',
            '      <div class="uploader__controls">',
            '        <label class="uploader__radio">',
            '          <input type="radio" :checked="i === primary" @change="setPrimary(i)" /> 主画像',
            '        </label>',
            '        <button type="button" @click="move(i, -1)" :disabled="i === 0" title="前へ">&uarr;</button>',
            '        <button type="button" @click="move(i, 1)" :disabled="i === items.length - 1" title="後へ">&darr;</button>',
            '        <button type="button" class="uploader__remove" @click="remove(i)">削除</button>',
            '      </div>',
            '    </li>',
            '  </ul>',
            '  <p v-if="items.length && !supportsDT" class="uploader__hint">',
            '    ※ このブラウザでは並べ替え・個別削除は送信に反映されません（選択した全ファイルがアップロードされます）。',
            '  </p>',
            '</div>'
        ].join("")
    }).mount("#image-preview");
})();
