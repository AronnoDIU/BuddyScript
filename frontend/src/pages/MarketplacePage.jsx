import { useEffect, useMemo, useState } from 'react';
import { marketplaceApi } from '../api/marketplace';
import { safetyApi } from '../api/safety';
import { resolveMediaUrl } from '../api';

const INITIAL_FORM = {
  title: '',
  description: '',
  priceAmount: '',
  currency: 'USD',
  category: 'general',
  conditionType: 'used',
  location: '',
  tags: '',
};

export default function MarketplacePage() {
  const [activeTab, setActiveTab] = useState('browse');
  const [loading, setLoading] = useState(true);
  const [listings, setListings] = useState([]);
  const [myListings, setMyListings] = useState([]);
  const [query, setQuery] = useState('');
  const [category, setCategory] = useState('');
  const [error, setError] = useState('');
  const [form, setForm] = useState(INITIAL_FORM);
  const [image, setImage] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [reportReason, setReportReason] = useState({});

  const visibleListings = useMemo(() => (activeTab === 'browse' ? listings : myListings), [activeTab, listings, myListings]);

  const loadData = async () => {
    setLoading(true);
    setError('');
    try {
      const [feedResponse, mineResponse] = await Promise.all([
        marketplaceApi.listListings({ q: query, category: category || undefined, limit: 40 }),
        marketplaceApi.myListings(),
      ]);
      setListings(feedResponse.data?.listings || []);
      setMyListings(mineResponse.data?.listings || []);
    } catch (loadError) {
      setError(loadError?.response?.data?.message || 'Failed to load marketplace listings.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const onSearch = async (event) => {
    event.preventDefault();
    await loadData();
  };

  const onCreate = async (event) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');
    try {
      await marketplaceApi.createListing({ ...form, priceAmount: Number(form.priceAmount || 0), image });
      setForm(INITIAL_FORM);
      setImage(null);
      await loadData();
      setActiveTab('my');
    } catch (submitError) {
      setError(submitError?.response?.data?.message || 'Failed to create listing.');
    } finally {
      setSubmitting(false);
    }
  };

  const onMarkSold = async (listingId) => {
    try {
      await marketplaceApi.markSold(listingId);
      await loadData();
    } catch (actionError) {
      setError(actionError?.response?.data?.message || 'Failed to mark listing as sold.');
    }
  };

  const onDelete = async (listingId) => {
    if (!window.confirm('Delete this listing?')) return;
    try {
      await marketplaceApi.deleteListing(listingId);
      await loadData();
    } catch (actionError) {
      setError(actionError?.response?.data?.message || 'Failed to delete listing.');
    }
  };

  const onReportListing = async (listing) => {
    const reason = (reportReason[listing.id] || '').trim();
    if (!reason) {
      setError('Please add a report reason before submitting.');
      return;
    }

    try {
      await safetyApi.submitReport({
        targetType: 'marketplace_listing',
        targetId: listing.id,
        category: 'marketplace',
        reason,
      });
      setReportReason((prev) => ({ ...prev, [listing.id]: '' }));
      setError('Report submitted. Thank you for helping keep the marketplace safe.');
    } catch (reportError) {
      setError(reportError?.response?.data?.message || 'Failed to submit report.');
    }
  };

  return (
    <div className="marketplace-page">
      <div className="profile_page_header _feed_inner_area _b_radious6 _padd_t24 _padd_b24 _padd_r24 _padd_l24">
        <div className="profile_page_header_row">
          <div>
            <h4 className="_title5 profile_page_heading">Marketplace</h4>
            <p className="profile_meta profile_page_heading_meta">Buy and sell items with built-in trust & safety reporting.</p>
          </div>
        </div>
      </div>

      <div className="profile_card" style={{ marginTop: 16 }}>
        <form onSubmit={onSearch} style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search listings" className="form-control" style={{ maxWidth: 320 }} />
          <input value={category} onChange={(event) => setCategory(event.target.value)} placeholder="Category" className="form-control" style={{ maxWidth: 220 }} />
          <button type="submit" className="profile_tab">Search</button>
          <button type="button" className={activeTab === 'browse' ? 'profile_tab profile_tab_active' : 'profile_tab'} onClick={() => setActiveTab('browse')}>Browse</button>
          <button type="button" className={activeTab === 'my' ? 'profile_tab profile_tab_active' : 'profile_tab'} onClick={() => setActiveTab('my')}>My Listings</button>
        </form>
      </div>

      <div className="profile_grid" style={{ marginTop: 16 }}>
        <aside className="profile_sidebar">
          <section className="profile_card">
            <h2 className="profile_section_title">Create listing</h2>
            <form onSubmit={onCreate} style={{ display: 'grid', gap: 8 }}>
              <input className="form-control" placeholder="Title" value={form.title} onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))} required />
              <textarea className="form-control" placeholder="Description" value={form.description} onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))} required rows={4} />
              <input className="form-control" type="number" min="0" placeholder="Price amount" value={form.priceAmount} onChange={(event) => setForm((prev) => ({ ...prev, priceAmount: event.target.value }))} required />
              <div style={{ display: 'flex', gap: 8 }}>
                <input className="form-control" placeholder="Currency" value={form.currency} onChange={(event) => setForm((prev) => ({ ...prev, currency: event.target.value }))} />
                <input className="form-control" placeholder="Condition" value={form.conditionType} onChange={(event) => setForm((prev) => ({ ...prev, conditionType: event.target.value }))} />
              </div>
              <input className="form-control" placeholder="Category" value={form.category} onChange={(event) => setForm((prev) => ({ ...prev, category: event.target.value }))} />
              <input className="form-control" placeholder="Location" value={form.location} onChange={(event) => setForm((prev) => ({ ...prev, location: event.target.value }))} />
              <input className="form-control" placeholder="Tags (comma/space separated)" value={form.tags} onChange={(event) => setForm((prev) => ({ ...prev, tags: event.target.value }))} />
              <input className="form-control" type="file" accept="image/*" onChange={(event) => setImage(event.target.files?.[0] || null)} />
              <button type="submit" className="profile_tab profile_tab_active" disabled={submitting}>{submitting ? 'Creating...' : 'Create Listing'}</button>
            </form>
          </section>
        </aside>

        <section className="profile_timeline">
          {error && <div className="profile_card" style={{ color: '#b42318' }}>{error}</div>}

          {loading ? (
            <div className="profile_card">Loading listings...</div>
          ) : visibleListings.length === 0 ? (
            <div className="profile_card">No listings found.</div>
          ) : (
            visibleListings.map((listing) => (
              <article key={listing.id} className="profile_post_card">
                <div className="profile_post_header">
                  <div>
                    <strong>{listing.title}</strong>
                    <p className="profile_meta profile_post_meta">{listing.category} • {listing.conditionType} • {listing.location || 'No location'}</p>
                  </div>
                  <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    <span className="profile_post_visibility">{listing.status}</span>
                    <span className="profile_post_visibility">{listing.currency} {listing.priceAmount}</span>
                  </div>
                </div>
                <p className="profile_meta profile_post_meta">Seller: {listing.seller?.displayName || 'Unknown'}</p>
                <p className="profile_post_content">{listing.description}</p>
                {listing.imageUrl && <img src={resolveMediaUrl(listing.imageUrl)} alt={listing.title} className="profile_post_image" />}
                <div className="profile_post_footer" style={{ gap: 8, display: 'flex', justifyContent: 'space-between' }}>
                  <span>{(listing.tags || []).join(', ') || 'No tags'}</span>
                  <span>Posted {new Date(listing.createdAt).toLocaleString()}</span>
                </div>

                {activeTab === 'my' ? (
                  <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
                    <button type="button" className="profile_tab" onClick={() => onMarkSold(listing.id)}>Mark Sold</button>
                    <button type="button" className="profile_tab" onClick={() => onDelete(listing.id)}>Delete</button>
                  </div>
                ) : (
                  <div style={{ display: 'grid', gap: 8, marginTop: 8 }}>
                    <input
                      className="form-control"
                      placeholder="Report reason"
                      value={reportReason[listing.id] || ''}
                      onChange={(event) => setReportReason((prev) => ({ ...prev, [listing.id]: event.target.value }))}
                    />
                    <button type="button" className="profile_tab" onClick={() => onReportListing(listing)}>Report Listing</button>
                  </div>
                )}
              </article>
            ))
          )}
        </section>
      </div>
    </div>
  );
}

