@extends('layouts.app', ['title' => 'Suggest an Update'])

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <p class="text-uppercase text-muted small mb-2">Candidate Edit</p>
                <h1 class="h3 mb-2">Suggest an Update</h1>
                <p class="text-muted mb-0">
                    Propose structured changes for <a href="{{ route('items.show', $item) }}">{{ $item->english_name }}</a>.
                </p>
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                <a class="btn btn-outline-primary" href="{{ route('items.show', $item) }}">Back to Pair</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-12">
                @include('items.candidate-edits._criteria')
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('items.candidate-edits.store', $item) }}">
            @csrf

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Proposal Summary</h2>
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" class="form-control" value="{{ old('title') }}" placeholder="Short label for this proposed change">
                    </div>
                    <div class="form-group mb-0">
                        <label for="summary">Why should this be changed?</label>
                        <textarea id="summary" name="summary" rows="4" class="form-control" required>{{ old('summary') }}</textarea>
                        <small class="form-text text-muted">Explain what is wrong, what should be updated, and where the information came from.</small>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Core Details</h2>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="english_name">Pair Name</label>
                            <input id="english_name" name="english_name" type="text" class="form-control" value="{{ old('english_name', $item->english_name) }}" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="year">Release Year</label>
                            <input id="year" name="year" type="number" class="form-control" value="{{ old('year', $item->year) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="foreign_name">Original / Alternate Name</label>
                        <input id="foreign_name" name="foreign_name" type="text" class="form-control" value="{{ old('foreign_name', $item->foreign_name) }}">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="brand_id">Brand</label>
                            <select id="brand_id" name="brand_id" class="form-control" required>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id) === $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="product_number">Style Code / SKU</label>
                            <input id="product_number" name="product_number" type="text" class="form-control" value="{{ old('product_number', $item->product_number) }}">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="category_ids">Categories</label>
                        <select id="category_ids" name="category_ids[]" class="form-control" multiple required size="6">
                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected(in_array($category->id, old('category_ids', $item->categories->pluck('id')->all()), true))
                                >{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Price and Notes</h2>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="currency">Currency</label>
                            <select id="currency" name="currency" class="form-control">
                                <option value="">Unknown</option>
                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $item->currency) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="price">Price</label>
                            <input id="price" name="price" type="number" step="0.01" min="0" class="form-control" value="{{ old('price', $item->price) }}">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="6" class="form-control">{{ old('notes', strip_tags((string) $item->notes)) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Relationships</h2>

                    <div class="form-group">
                        <label for="feature_ids">Features</label>
                        <select id="feature_ids" name="feature_ids[]" class="form-control" multiple size="6">
                            @foreach ($features as $feature)
                                <option value="{{ $feature->id }}" @selected(in_array($feature->id, old('feature_ids', $item->features->pluck('id')->all()), true))>{{ $feature->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="color_ids">Colorways</label>
                        <select id="color_ids" name="color_ids[]" class="form-control" multiple size="6">
                            @foreach ($colors as $color)
                                <option value="{{ $color->id }}" @selected(in_array($color->id, old('color_ids', $item->colors->pluck('id')->all()), true))>{{ $color->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label for="tag_ids">Tags</label>
                        <select id="tag_ids" name="tag_ids[]" class="form-control" multiple size="6">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tag_ids', $item->tags->pluck('id')->all()), true))>{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Technical Specs</h2>
                    <div class="form-row">
                        @foreach ($attributes as $attribute)
                            <div class="form-group col-md-6">
                                <label for="attribute-{{ $attribute->id }}">{{ $attribute->name }}</label>
                                <input
                                    id="attribute-{{ $attribute->id }}"
                                    name="attributes[{{ $attribute->id }}]"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('attributes.'.$attribute->id, optional($item->attributes->firstWhere('id', $attribute->id))->pivot->value) }}"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="text-center mb-5">
                <button type="submit" class="btn btn-primary btn-lg">Submit Candidate Edit</button>
            </div>
        </form>
    </div>
@endsection
