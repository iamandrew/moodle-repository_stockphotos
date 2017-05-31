Stock Photos repository
===========================
Moodle repository plugin used to browse free stock photos from Unsplash.com.

This plugin requires the use of the Unsplash API and due to rate limits, will require your own API account and application ID.

Requirements
------------
- API account from [Unsplash](https://unsplash.com/developers)
- New unsplash application set up

Installation
------------
- Download zip file
- Copy it in moodle/repository directory
- Unzip it and remove zip file
- Log in as administrator to your moodle site
- Follow online installation procedure
- Set as Enabled and Visible the Stock Photos repository in Administration Block -> Site Administration -> Plugins -> Repositories -> Manage repositories
- Fill ApplicationID field on configuration page.

Unsplash API Limitations
-------------------
The unsplash API has several requirements and limitations.
Your developer application will be rate limited to 50 requests per hour.
You can apply for production status and have this upgraded to 5000 per hour, but must provide screenshots showing compliance with several rules.
Screenshots of the repository interfaces will show compliance will all unsplash API guidelines

Unsplash API Setup
-------------------
You can follow the instructions [here](https://unsplash.com/documentation#creating-a-developer-account) to set up your unsplash api account and register a new application

Since this plugin only uses the public actions, only your ApplicationID is required as no user authentication is needed.


Credits
---------------------
Plugin built and maintained by Andrew Davidson

Icon made by [Madebyoliver](http://www.flaticon.com/authors/madebyoliver) from [flaticon.com](www.flaticon.com)