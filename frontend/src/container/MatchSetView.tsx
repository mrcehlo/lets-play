import * as React from "react";

import {RouteComponentProps, withRouter} from "react-router";
import {merge} from "typescript-object-utils";
import {MatchSetView as Component} from "../component/MatchSetView";
import {Client} from "../infrastructure/http/Client";
import {httpContextValidationMap} from "../infrastructure/http/Provider";
import {Response} from "../infrastructure/http/Response";
import {Match, MatchSet} from "../model/models";

export interface MatchSetViewProps {
	matchId?: string;
}

class MatchSetViewWithRouter extends React.PureComponent<MatchSetViewProps & RouteComponentProps<any>, {}> {

	public static contextTypes = httpContextValidationMap;

	public render(): JSX.Element {
		return (
			<Component
				matchId={this.props.matchId}
				loadMatchSet={this.loadMatchSet}
				onSave={this.saveMatchSet}
			/>
		);
	}

	private loadMatchSet = (matchId: string): Promise<MatchSet> => {
		const client: Client = this.context.httpClient;
		return client.request({url: "/match-sets/" + matchId, method: "GET"})
			.then((response: Response<MatchSet>) => {
				const set = response.data;
				return {
					id: set.id,
					name: set.name,
					matches: set.matches.map((m: Match) => {
						return merge(m, {startDate: m.startDate.substr(0, m.startDate.length - 9)});
					})
				};
			});
	};
	private saveMatchSet = (set: MatchSet) => {
		const client: Client = this.context.httpClient;
		if (set.id) {
			client.request({url: "/match-sets/" + set.id, method: "PUT", data: set});
		} else {
			client
				.request({url: "/match-sets", method: "POST", data: set})
				.then((response: Response<MatchSet>) => {
					this.props.history.push("/match-sets/" + response.data.id);
				});
		}
	};
}

export const MatchSetView = withRouter(MatchSetViewWithRouter);