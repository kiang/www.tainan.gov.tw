let rightNow = new Date();
let theDate = rightNow.toISOString().slice(0,10);
$(function() {
    $('#theDate').datepicker({
        dateFormat: 'yy-mm-dd'
    })
    .val(theDate)
    .change(function() {
        theDate = $(this).val();
        getNews(theDate);
    })
    .trigger('change');
})

function getNews(theDate) {
    $('div.row').html('');
    let theYear = theDate.slice(0,4);
    let theMonth = theDate.slice(5,7);
    let metaFile = 'data/' + theYear + '/' + theDate + '.json';
    $.getJSON(metaFile, {}, function(c) {
        for(k in c) {
            let dataFile = 'data/' + theYear + '/' + theMonth + '/' + theDate + '_' + k + '.json';
            $.getJSON(dataFile, {}, function(d) {
                let block = '<div class="col-md-12"><div class="card mb-12 shadow-sm"><div class="card-body">';
                block += '<p class="card-text">' + d['title'] + '<pre>' + d['content'] + '</pre></p>';
                block += '<div class="d-flex justify-content-between align-items-center"><div class="btn-group">';
                block += '<a class="btn btn-sm btn-outline-secondary" href="' + d['url'] + '" target="_blank">連結</a>';
                block += '</div></div>';
                block += '</div></div></div>';
                $('div.row').append(block);
            })
        }
    })
}