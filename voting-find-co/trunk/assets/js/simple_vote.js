jQuery(document).ready(function ($) {

    let voting_buttons = $('.voting-btn');
    let result_el = $('.button-head');


    voting_buttons.on('click', function () {
        let vote_type = '';
        if (this.classList.contains('vote-up')) {
            vote_type = 'positive';
        } else {
            vote_type = 'negative';
        }

        let post_id = this.dataset.postId;

        jQuery.post(
            votingData.ajaxurl, {
                action: 'save_vote',
                security: votingData.nonce,
                vote_type: vote_type,
                post_id: post_id
            }, function (response) {
                var data = JSON.parse(response);
                voting_buttons.prop('disabled', true);
                if (vote_type === 'positive') {
                    $('.vote-up').addClass('active');
                    $('.vote-up span').text(data.positive_percentage + '%');
                }
                if (vote_type === 'negative') {
                    $('.vote-down').addClass('active');
                    $('.vote-down span').text(data.negative_percentage + '%');
                }

                result_el.text(`Thank you for your feedback.`);
            });
    });
});