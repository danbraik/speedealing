"use strict";

var mongoose = require('mongoose'),
		gridfs = require('../controllers/gridfs'),
		nodemailer = require("nodemailer"),
		_ = require('underscore'),
		dateFormat = require('dateformat'),
		config = require(__dirname + '/../../config/config');

var CommandeModel = mongoose.model('commande');
var ContactModel = mongoose.model('contact');
var ExtrafieldModel = mongoose.model('extrafields');
var DictModel = mongoose.model('dict');
var SocieteModel = mongoose.model('societe');

var smtpTransport = nodemailer.createTransport("SMTP", config.transport);

module.exports = function(app, passport, auth) {

	var object = new Object();

	ExtrafieldModel.findById('extrafields:Commande', function(err, doc) {
		if (err) {
			console.log(err);
			return;
		}

		object.fk_extrafields = doc;
	});
	app.get('/api/commande/lines/list', auth.requiresLogin, object.listLines);
	app.get('/api/commande', auth.requiresLogin, object.all);
	app.post('/api/commande', auth.requiresLogin, object.create);
	app.get('/api/commande/select', auth.requiresLogin, object.select);
	app.get('/api/commande/:orderId', auth.requiresLogin, object.show);
	app.put('/api/commande/:orderId', auth.requiresLogin, object.update);
	app.del('/api/commande/:orderId', auth.requiresLogin, object.destroy);
	app.post('/api/commande/file/:Id', auth.requiresLogin, object.createFile);
	app.get('/api/commande/file/:Id/:fileName', auth.requiresLogin, object.getFile);
	app.del('/api/commande/file/:Id/:fileName', auth.requiresLogin, object.deleteFile);
	app.get('/api/commande/pdf/:orderId', auth.requiresLogin, object.genPDF);

	//Finish with setting up the orderId param
	app.param('orderId', object.order);
	//other routes..
};

function Object() {
}

Object.prototype = {
	listLines: function(req, res) {
		CommandeModel.findOne({_id: req.query.id}, "lines", function(err, doc) {
			if (err) {
				console.log(err);
				res.send(500, doc);
				return;
			}

			res.send(200, doc.lines);
		});
	},
	/**
	 * Find order by id
	 */
	order: function(req, res, next, id) {
		CommandeModel.findOne({_id: id}, function(err, doc) {
			if (err)
				return next(err);
			if (!doc)
				return next(new Error('Failed to load order ' + id));
			req.order = doc;
			next();
		});
	},
	/**
	 * Create an order
	 */
	create: function(req, res) {
		var order = new CommandeModel(req.body);
		order.author = {};
		order.author.id = req.user._id;
		order.author.name = req.user.name;

		if (order.entity == null)
			order.entity = req.user.entity;

		if (req.user.societe.id) { // It's an external order
			return ContactModel.findOne({'societe.id': req.user.societe.id}, function(err, contact) {
				if (err)
					console.log(err);

				if (contact == null)
					contact = new ContactModel();

				contact.entity = req.user.entity;
				contact.firstname = req.user.firstname;
				contact.lastname = req.user.lastname;
				contact.email = req.user.email;


				contact.societe.id = req.user.societe.id;
				contact.societe.name = req.user.societe.name;

				contact.name = contact.firstname + " " + contact.lastname;


				//console.log(contact);
				contact.save(function(err, doc) {
					if (err)
						console.log(err);

					//console.log(doc);

					order.contact.id = doc._id;
					order.contact.name = doc.name;

					order.client.id = req.user.societe.id;
					order.client.name = req.user.societe.name;

					order.save(function(err, doc) {
						if (err)
							return console.log(err);

						res.json(doc);
					});

				});
			});
		}
		
		console.log(order);

		order.save(function(err, doc) {
			if (err) {
				return console.log(err);
			}

			res.json(doc);
		});
	},
	/**
	 * Update an order
	 */
	update: function(req, res) {
		var order = req.order;
		order = _.extend(order, req.body);

		if (req.user.societe.id && order.Status == "NEW") { // It's an external order
			console.log("Mail order");

			// Send an email
			var mailOptions = {
				from: "ERP Speedealing<no-reply@speedealing.com>",
				to: "Plan 92 Chaumeil<plan92@chaumeil.net>",
				cc: "herve.prot@symeos.com",
				subject: "Nouvelle commande " + order.client.name + " - " + order.ref + " dans l'ERP"
			};

			mailOptions.text = "La commande " + order.ref + " vient d'etre cree \n";
			mailOptions.text += "Pour consulter la commande cliquer sur ce lien : ";
			mailOptions.text += '<a href="http://erp.chaumeil.net/commande/fiche.php?id=' + order._id + '">' + order.ref + '</a>';
			mailOptions.text += "\n\n";

			// send mail with defined transport object
			smtpTransport.sendMail(mailOptions, function(error, response) {
				if (error) {
					console.log(error);
				} else {
					console.log("Message sent: " + response.message);
				}

				// if you don't want to use this transport object anymore, uncomment following line
				smtpTransport.close(); // shut down the connection pool, no more messages
			});
		}


		order.save(function(err, doc) {
			res.json(doc);
		});
	},
	/**
	 * Delete an order
	 */
	destroy: function(req, res) {
		var order = req.order;
		order.remove(function(err) {
			if (err) {
				res.render('error', {
					status: 500
				});
			} else {
				res.json(order);
			}
		});
	},
	/**
	 * Show an order
	 */
	show: function(req, res) {
		res.json(req.order);
	},
	/**
	 * List of orders
	 */
	all: function(req, res) {
		var query = {};

		if (req.query) {
			for (var i in req.query) {
				if (i == "query") {
					var status = ["SHIPPING", "CLOSED", "CANCELED", "BILLING"];

					switch (req.query[i]) {
						case "NOW" :
							query.Status = {"$nin": status};
							break;
						case "CLOSED" :
							query.Status = {"$in": status};
							break;
						default :
							break;
					}
				} else
					query[i] = req.query[i];
			}
		}

		CommandeModel.find(query, "-files -latex", function(err, orders) {
			if (err)
				return res.render('error', {
					status: 500
				});

			res.json(orders);
		});
	},
	/**
	 * Add a file in an order
	 */
	createFile: function(req, res) {
		var id = req.params.Id;
		//console.log(id);
		//console.log(req.body);

		if (req.files && id) {
			//console.log(req.files);

			/* Add dossier information in filename */
			if (req.body.idx)
				req.files.file.originalFilename = req.body.idx + "___" + req.files.file.originalFilename;

			gridfs.addFile(CommandeModel, id, req.files.file, function(err, result) {
				//console.log(result);
				if (err)
					res.send(500, err);
				else
					res.send(200, result);
			});
		} else
			res.send(500, "Error in request file");
	},
	/**
	 * Get a file form an order
	 */
	getFile: function(req, res) {
		var id = req.params.Id;
		if (id && req.params.fileName) {

			gridfs.getFile(CommandeModel, id, req.params.fileName, function(err, store) {
				if (err)
					return res.send(500, err);
				if (req.query.download)
					res.attachment(store.filename); // for downloading 

				res.type(store.contentType);
				store.stream(true).pipe(res);
			});
		} else {
			res.send(500, "Error in request file");
		}

	},
	/**
	 * Delete a file in an order
	 */
	deleteFile: function(req, res) {
		//console.log(req.body);
		var id = req.params.Id;
		//console.log(id);

		if (req.params.fileName && id) {
			gridfs.delFile(CommandeModel, id, req.params.fileName, function(err) {
				if (err)
					res.send(500, err);
				else
					res.send(200, {status: "ok"});
			});
		} else
			res.send(500, "File not found");
	},
	genPDF: function(req, res) {
		var latex = require('../models/latex');

		latex.loadModel("order.tex", function(err, tex) {
			var doc = req.order;

			SocieteModel.findOne({_id: doc.client.id}, function(err, societe) {

				// replacement des variables
				tex = tex.replace(/--NUM--/g, doc.ref.replace(/_/g, "\\_"));
				tex = tex.replace(/--DESTINATAIRE--/g, "\\textbf{\\large " + doc.billing.name + "} \\\\" + doc.billing.address + "\\\\ \\textsc{" + doc.billing.zip + " " + doc.billing.town + "}");
				tex = tex.replace(/--CODECLIENT--/g, societe._id);
				tex = tex.replace(/--TITLE--/g, doc.ref_client);
				tex = tex.replace(/--DATEC--/g, dateFormat(doc.datec, "dd/mm/yyyy"));
				tex = tex.replace(/--DATEL--/g, dateFormat(doc.date_livraison, "dd/mm/yyyy"));

				//tex = tex.replace(/--NOTE--/g, doc.desc.replace(/\n/g, "\\\\"));
				tex = tex.replace(/--NOTE--/g, "");

				//console.log(doc);

				var products = [];

				for (var i = 0; i < doc.notes.length; i++)
					products.push(doc.notes[i]);

				//console.log(product);


				//console.log(products);

				var tab_latex = "";

				for (var i = 0; i < products.length; i++) {
					//tab_latex += products[i].title.replace(/_/g, "\\_") + "&" + products[i].note.replace(/<br\/>/g,"\\\\") + "& & \\tabularnewline\n";
					tab_latex += products[i].title.replace(/_/g, "\\_") + "&\\specialcell[t]{" + products[i].note.replace(/<br\/>/g, "\\\\").replace(/<br \/>/g, "\\\\").replace(/<p>/g, "").replace(/<\/p>/g, "\\\\").replace(/<a.*>/g, "\\\\") + "}& & \\tabularnewline\n";
				}

				//tab_latex += "&\\specialcell[t]{" + doc.desc.replace(/\n/g, "\\\\") + "}& & \\tabularnewline\n";

				tex = tex.replace("--TABULAR--", tab_latex);

				latex.headfoot(doc.entity, tex, function(tex) {

					tex = tex.replace(/undefined/g, "");

					doc.latex.data = new Buffer(tex);
					doc.latex.createdAt = new Date();
					doc.latex.title = "Order " + doc.ref;

					doc.save(function(err) {
						if (err) {
							console.log("Error while trying to save this document");
							res.send(403, "Error while trying to save this document");
						}

						latex.compileDoc(doc._id, doc.latex, function(result) {
							if (result.errors.length) {
								//console.log(pdf);
								return res.send(500, result.errors);
							}

							return latex.getPDF(result.compiledDocId, function(err, pdfPath) {
								res.type('application/pdf');
								res.attachment(doc.ref + ".pdf"); // for douwnloading
								res.sendfile(pdfPath);
							});
						});
					});
				});
			});
		});
	},
	select: function(req, res) {
		ExtrafieldModel.findById('extrafields:Commande', function(err, doc) {
			if (err) {
				console.log(err);
				return;
			}
			var result = [];
			if (doc.fields[req.query.field].dict)
				return DictModel.findOne({_id: doc.fields[req.query.field].dict}, function(err, docs) {

					if (docs) {
						for (var i in docs.values) {
							if (docs.values[i].enable) {
								var val = {};
								val.id = i;
								if (docs.values[i].label)
									val.label = req.i18n.t("orders:" + docs.values[i].label);
								else
									val.label = req.i18n.t("orders:" + i);
								
								if (docs.values[i].cssClass)
									val.css = docs.values[i].cssClass;
								
								result.push(val);
							}
						}
						doc.fields[req.query.field].values = result;
					}
					res.json(doc.fields[req.query.field]);
				});

			for (var i in doc.fields[req.query.field].values) {
				if (doc.fields[req.query.field].values[i].enable) {
					var val = {};
					val.id = i;
					val.label = doc.fields[req.query.field].values[i].label;
					result.push(val);
				}
			}
			doc.fields[req.query.field].values = result;

			res.json(doc.fields[req.query.field]);
		});
	}
};