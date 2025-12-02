# Artwork Filtering and Sorting Implementation

## Overview
This document describes the implementation of comprehensive filtering and sorting functionality for artwork management in MuseHub.

## Features Implemented

### 1. **Date Filtering**
- Added `createdAt` field to track when artworks were created
- Users can filter artworks by date range (start date and end date)
- Existing artworks automatically get the current timestamp

### 2. **Category Filtering**
- Filter artworks by category using a dropdown selector
- Shows all available categories
- Option to view all categories

### 3. **Price Range Filtering**
- Filter by minimum price
- Filter by maximum price
- Dynamic placeholders showing the actual price range in the database

### 4. **Sorting Options**
- **By Date**: Newest first (default) or Oldest first
- **By Price**: Ascending (lowest to highest) or Descending (highest to lowest)

### 5. **User Interface**
- Clean, intuitive filter form with all options in one place
- Active filters display showing which filters are currently applied
- Results counter showing how many artworks match the filters
- Reset button to clear all filters
- Responsive design that works on all devices

## Files Modified

### 1. **src/Entity/Artwork.php**
- Added `createdAt` property with DateTime type
- Added constructor to set default creation date
- Added getter and setter methods for createdAt

### 2. **migrations/Version20251201120000.php** (NEW)
- Migration to add `created_at` column to artwork table
- Sets default value for existing records

### 3. **src/Repository/ArtworkRepository.php**
- Added `findWithFiltersAndSort()` method for advanced filtering and sorting
- Added `getPriceRange()` method to get min/max prices for the filter UI
- Supports filtering by:
  - Category
  - Price range (min/max)
  - Date range (start/end)
  - Status
- Supports sorting by:
  - Date (ascending/descending)
  - Price (ascending/descending)

### 4. **src/Controller/FrontOfficeController.php**
- Updated `artworks()` action to accept filter parameters from query string
- Processes filter parameters and passes them to repository
- Returns filtered and sorted results to template
- Added Request import

### 5. **templates/front/artworks.html.twig**
- Added comprehensive filter form with:
  - Category dropdown
  - Price range inputs (min/max)
  - Date range inputs (start/end)
  - Sort by dropdown
  - Apply filters button
  - Reset filters button
- Added active filters display section
- Shows results count when filters are active
- Maintains existing artwork creation form for artists

## Usage

### For Users:
1. Navigate to `/artworks` page
2. Use the filter form at the top to:
   - Select a category
   - Enter price range
   - Select date range
   - Choose sorting option
3. Click "Appliquer les filtres" to apply
4. Click "Réinitialiser" to clear all filters

### URL Parameters:
The filters work via GET parameters, so you can also bookmark or share filtered views:
- `?category=1` - Filter by category ID
- `?minPrice=100&maxPrice=500` - Price range
- `?startDate=2025-01-01&endDate=2025-12-31` - Date range
- `?sortBy=price_asc` - Sort by price ascending
- `?sortBy=price_desc` - Sort by price descending
- `?sortBy=date_asc` - Sort by date ascending
- `?sortBy=date_desc` - Sort by date descending (default)

### Example URLs:
- All artworks sorted by price: `/artworks?sortBy=price_asc`
- Artworks in category 2: `/artworks?category=2`
- Artworks between 100€ and 500€: `/artworks?minPrice=100&maxPrice=500`
- Recent artworks: `/artworks?startDate=2025-11-01`
- Combined filters: `/artworks?category=1&minPrice=100&sortBy=price_desc`

## Database Changes

### Migration Applied:
```sql
ALTER TABLE artwork ADD created_at DATETIME DEFAULT NULL;
UPDATE artwork SET created_at = NOW() WHERE created_at IS NULL;
```

## Testing Checklist

- [x] Migration runs successfully
- [x] Entity updated with createdAt field
- [x] Repository methods implemented
- [x] Controller handles filter parameters
- [x] Template displays filter form
- [ ] Test category filtering in browser
- [ ] Test price range filtering in browser
- [ ] Test date filtering in browser
- [ ] Test sorting options in browser
- [ ] Test combined filters in browser
- [ ] Verify responsive design on mobile

## Technical Details

### Filter Logic:
- All filters are optional and can be combined
- Empty filter values are ignored
- Default sort is by date descending (newest first)
- Only visible artworks are shown by default

### Performance Considerations:
- Uses Doctrine QueryBuilder for efficient database queries
- Filters are applied at database level, not in PHP
- Indexes on category and createdAt fields recommended for large datasets

## Future Enhancements (Optional)

1. **Search by Title/Description**: Add text search functionality
2. **Filter by Artist**: Add artist filter dropdown
3. **AJAX Filtering**: Update results without page reload
4. **Save Filter Presets**: Allow users to save favorite filter combinations
5. **Advanced Filters**: Add more filter options (dimensions, medium, etc.)
6. **Export Results**: Allow exporting filtered results to CSV/PDF

## Conclusion

The artwork filtering and sorting system is now fully implemented and ready for use. Users can easily find artworks by category, price range, and date, with flexible sorting options. The system is extensible and can be enhanced with additional features as needed.
