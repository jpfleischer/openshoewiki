@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="mb-4 text-center">
                <h1 class="h2 mb-3">Submit a New Pair</h1>
                <p class="text-muted mb-0">
                    Add a shoe record for review. Phase 1 writes directly into the archive and marks the pair as pending.
                </p>
                <div class="mt-3">
                    <button type="button" id="paste-extracted-json" class="btn btn-outline-primary">Paste Extracted JSON</button>
                </div>
                <div id="paste-extracted-status" class="small text-muted mt-2"></div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('submit.store') }}" method="post" enctype="multipart/form-data">
                @csrf

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Core Details</h2>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="english_name">Pair Name <span class="text-danger">*</span></label>
                                    <input id="english_name" name="english_name" type="text" class="form-control" value="{{ old('english_name') }}" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="year">Release Year</label>
                                    <input id="year" name="year" type="number" min="1900" max="{{ date('Y') + 1 }}" class="form-control" value="{{ old('year') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="foreign_name">Original / Alternate Name</label>
                            <input id="foreign_name" name="foreign_name" type="text" class="form-control" value="{{ old('foreign_name') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brand_id">Brand <span class="text-danger">*</span></label>
                                    <select id="brand_id" name="brand_id" class="form-control form-control-chosen" required>
                                        <option value="">Select a brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected((string) $brand->id === old('brand_id'))>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_number">Style Code / SKU</label>
                                    <input id="product_number" name="product_number" type="text" class="form-control" value="{{ old('product_number') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="category_ids">Categories <span class="text-danger">*</span></label>
                            <select id="category_ids" name="category_ids[]" class="form-control form-control-chosen" multiple required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_ids', []), true))>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Choose one or more, such as sneakers, loafers, boots, or sandals.</small>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Media and Pricing</h2>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image">Primary Image</label>
                                    <input id="image" name="image" type="file" class="form-control-file" accept="image/*">
                                    <small class="form-text text-muted">Optional. If omitted, the default placeholder image is used.</small>
                                </div>

                                <div class="border rounded p-3 bg-light" id="image-paste-zone" tabindex="0" style="cursor: text;">
                                    <p class="font-weight-bold mb-2">Paste an image here</p>
                                    <p class="text-muted small mb-2">Copy an image to your clipboard, click this box, then press `Ctrl+V`.</p>
                                    <div id="image-paste-empty" class="small text-muted">No clipboard image pasted yet.</div>
                                    <div id="image-paste-preview" class="d-none">
                                        <img id="image-paste-preview-img" src="" alt="Pasted primary image preview" class="img-fluid rounded shadow-sm mb-2" style="max-height: 240px;">
                                        <div class="small text-success">Clipboard image attached as the primary image.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="images">Additional Images</label>
                                    <input id="images" name="images[]" type="file" class="form-control-file" accept="image/*" multiple>
                                    <small class="form-text text-muted">Optional. Up to 8 extra images.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="currency">Currency</label>
                                    <select id="currency" name="currency" class="form-control form-control-chosen">
                                        <option value="">Unknown</option>
                                        @foreach ($currencies as $code => $currency)
                                            <option value="{{ $code }}" @selected($code === old('currency', 'usd'))>{{ $currency }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="price">Retail Price</label>
                                    <input id="price" name="price" type="number" min="0" step="0.01" class="form-control" value="{{ old('price') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Classification</h2>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="feature_ids">Features</label>
                                    <select id="feature_ids" name="feature_ids[]" class="form-control form-control-chosen" multiple>
                                        @foreach ($features as $feature)
                                            <option value="{{ $feature->id }}" @selected(in_array($feature->id, old('feature_ids', []), true))>{{ $feature->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="color_ids">Colorways</label>
                                    <select id="color_ids" name="color_ids[]" class="form-control form-control-chosen" multiple>
                                        @foreach ($colors as $color)
                                            <option value="{{ $color->id }}" @selected(in_array($color->id, old('color_ids', []), true))>{{ $color->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tag_ids">Tags</label>
                                    <select id="tag_ids" name="tag_ids[]" class="form-control form-control-chosen" multiple>
                                        @foreach ($tags as $tag)
                                            <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tag_ids', []), true))>{{ $tag->slug }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Technical Specs</h2>
                        <p class="text-muted">Fill any fields that are relevant. These map into the archive's attribute system.</p>

                        <div class="row">
                            @foreach ($attributes as $attribute)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="attribute_{{ $attribute->id }}">{{ $attribute->name }}</label>
                                        <input
                                            id="attribute_{{ $attribute->id }}"
                                            name="attributes[{{ $attribute->id }}]"
                                            type="text"
                                            class="form-control"
                                            value="{{ old('attributes.' . $attribute->id) }}"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">Notes</h2>
                        <div class="form-group mb-0">
                            <label for="notes">Notes for reviewers and future editors</label>
                            <textarea id="notes" name="notes" rows="6" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5">Submit Pair</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(function () {
        var pasteZone = document.getElementById('image-paste-zone');
        var imageInput = document.getElementById('image');
        var emptyState = document.getElementById('image-paste-empty');
        var preview = document.getElementById('image-paste-preview');
        var previewImage = document.getElementById('image-paste-preview-img');

        var activePreviewUrl = null;

        var updatePreview = function (file) {
            if (activePreviewUrl) {
                URL.revokeObjectURL(activePreviewUrl);
                activePreviewUrl = null;
            }

            if (! file) {
                preview.classList.add('d-none');
                emptyState.classList.remove('d-none');
                previewImage.removeAttribute('src');
                return;
            }

            activePreviewUrl = URL.createObjectURL(file);
            previewImage.src = activePreviewUrl;
            preview.classList.remove('d-none');
            emptyState.classList.add('d-none');
        };

        if (pasteZone && imageInput && window.DataTransfer) {
            imageInput.addEventListener('change', function () {
                updatePreview(imageInput.files[0] || null);
            });

            pasteZone.addEventListener('click', function () {
                pasteZone.focus();
            });

            pasteZone.addEventListener('paste', function (event) {
                var items = Array.from((event.clipboardData || {}).items || []);
                var imageItem = items.find(function (item) {
                    return item.type && item.type.indexOf('image/') === 0;
                });

                if (! imageItem) {
                    return;
                }

                var file = imageItem.getAsFile();

                if (! file) {
                    return;
                }

                event.preventDefault();

                var extension = (file.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
                var pastedFile = new File([file], 'clipboard-image.' + extension, { type: file.type });
                var dataTransfer = new DataTransfer();

                dataTransfer.items.add(pastedFile);
                imageInput.files = dataTransfer.files;

                updatePreview(pastedFile);
            });
        }

        var importButton = document.getElementById('paste-extracted-json');
        var importStatus = document.getElementById('paste-extracted-status');

        var setImportStatus = function (message, isError) {
            if (! importStatus) {
                return;
            }

            importStatus.textContent = message;
            importStatus.classList.toggle('text-danger', !! isError);
            importStatus.classList.toggle('text-muted', ! isError);
        };

        var normalizeLabel = function (value) {
            return String(value || '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, ' ')
                .trim();
        };

        var setChosenValue = function (element, value) {
            if (! element || ! value) {
                return false;
            }

            var wanted = normalizeLabel(value);
            var matchedOption = Array.from(element.options).find(function (option) {
                return normalizeLabel(option.textContent) === wanted;
            });

            if (! matchedOption) {
                return false;
            }

            if (element.tomselect) {
                element.tomselect.setValue(matchedOption.value, true);
            } else {
                element.value = matchedOption.value;
                $(element).trigger('change');
            }

            return true;
        };

        var setChosenMultiValue = function (element, values) {
            if (! element || ! Array.isArray(values) || ! values.length) {
                return 0;
            }

            var wanted = values.map(normalizeLabel);
            var matchedValues = Array.from(element.options)
                .filter(function (option) {
                    var label = normalizeLabel(option.textContent);

                    return wanted.some(function (value) {
                        return value === label || value.indexOf(label) !== -1 || label.indexOf(value) !== -1;
                    });
                })
                .map(function (option) {
                    return option.value;
                });

            if (! matchedValues.length) {
                return 0;
            }

            if (element.tomselect) {
                element.tomselect.setValue(matchedValues, true);
            } else {
                $(element).val(matchedValues).trigger('change');
            }

            return matchedValues.length;
        };

        var setAttributeByLabel = function (labelNeedles, value) {
            if (! value) {
                return false;
            }

            var labels = Array.from(document.querySelectorAll('label[for^="attribute_"]'));
            var normalizedValue = String(value).trim();
            var matchedLabel = labels.find(function (label) {
                var normalized = normalizeLabel(label.textContent);
                return labelNeedles.some(function (needle) {
                    return normalized.indexOf(needle) !== -1;
                });
            });

            if (! matchedLabel) {
                return false;
            }

            var input = document.getElementById(matchedLabel.getAttribute('for'));
            if (! input) {
                return false;
            }

            input.value = normalizedValue;
            return true;
        };

        var parsePrice = function (value) {
            if (! value) {
                return null;
            }

            var match = String(value).match(/(\d+(?:\.\d+)?)/);
            return match ? match[1] : null;
        };

        var inferCurrency = function (value) {
            if (! value) {
                return null;
            }

            var raw = String(value).trim().toUpperCase();

            if (raw.indexOf('$') !== -1 || raw.indexOf('USD') === 0) {
                return 'usd';
            }

            if (raw.indexOf('CNY') === 0 || raw.indexOf('RMB') === 0) {
                return 'cny';
            }

            if (raw.indexOf('JPY') === 0) {
                return 'jpy';
            }

            if (raw.indexOf('EUR') === 0) {
                return 'eur';
            }

            if (raw.indexOf('GBP') === 0) {
                return 'gbp';
            }

            return null;
        };

        var buildNotes = function (data) {
            var sections = [];

            if (data.evidence) {
                sections.push('Imported extraction evidence:');
                Object.keys(data.evidence).forEach(function (key) {
                    if (data.evidence[key]) {
                        sections.push(key + ': ' + data.evidence[key]);
                    }
                });
            }

            sections.push('');
            sections.push('Imported extracted JSON:');
            sections.push(JSON.stringify(data, null, 2));

            return sections.join('\n').trim();
        };

        var importExtractedData = function (payload) {
            var data = payload && payload.response ? payload.response : payload;

            if (! data || typeof data !== 'object' || Array.isArray(data)) {
                throw new Error('Clipboard JSON does not look like an extracted shoe payload.');
            }

            var pairName = data.product_name || data.english_name || '';
            var brand = data.brand || '';
            var productType = data.product_type || '';
            var sku = data.sku_style_code || '';
            var price = data.price || '';
            var materials = Array.isArray(data.materials) ? data.materials.join(', ') : (data.materials || '');
            var heelHeight = data.heel_height || '';
            var platformHeight = data.platform_height || '';

            if (pairName) {
                document.getElementById('english_name').value = pairName;
            }

            if (sku) {
                document.getElementById('product_number').value = sku;
            }

            if (price) {
                var parsedPrice = parsePrice(price);
                if (parsedPrice) {
                    document.getElementById('price').value = parsedPrice;
                }

                var inferredCurrency = inferCurrency(price);
                if (inferredCurrency) {
                    var currencySelect = document.getElementById('currency');
                    if (currencySelect.tomselect) {
                        currencySelect.tomselect.setValue(inferredCurrency, true);
                    } else {
                        currencySelect.value = inferredCurrency;
                        $(currencySelect).trigger('change');
                    }
                }
            }

            var matchedBrand = setChosenValue(document.getElementById('brand_id'), brand);
            var matchedCategories = setChosenMultiValue(document.getElementById('category_ids'), [productType]);

            setAttributeByLabel(['heel height'], heelHeight);
            setAttributeByLabel(['platform height'], platformHeight);
            setAttributeByLabel(['upper material', 'material'], materials);

            var notesField = document.getElementById('notes');
            var importedNotes = buildNotes(data);
            notesField.value = notesField.value ? notesField.value + '\n\n' + importedNotes : importedNotes;

            setImportStatus(
                'Imported extracted JSON. ' +
                (matchedBrand ? 'Brand matched. ' : 'Brand needs review. ') +
                (matchedCategories ? 'Category matched.' : 'Category may need manual selection.'),
                false
            );
        };

        if (importButton && navigator.clipboard && navigator.clipboard.readText) {
            importButton.addEventListener('click', async function () {
                try {
                    var raw = await navigator.clipboard.readText();

                    if (! raw) {
                        throw new Error('Clipboard is empty.');
                    }

                    importExtractedData(JSON.parse(raw));
                } catch (error) {
                    setImportStatus(error.message || 'Could not import clipboard JSON.', true);
                }
            });
        }
    });
</script>
@endsection
